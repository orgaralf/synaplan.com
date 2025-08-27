<?php
/**
 * OIDC Authentication Class
 * 
 * Handles OpenID Connect authentication flow integration with the existing
 * Synaplan authentication system.
 * 
 * @package OIDC
 */

use Jumbojett\OpenIDConnectClient;

class OidcAuth {
    private static $client = null;
    
    /**
     * Initialize OIDC client
     */
    private static function initClient() {
        if (self::$client !== null) {
            return self::$client;
        }
        
        $providerUrl = ApiKeys::getOidcProviderUrl();
        $clientId = ApiKeys::getOidcClientId();
        $clientSecret = ApiKeys::getOidcClientSecret();
        
        // Validate that all required values are non-empty strings
        if (empty($providerUrl) || !is_string($providerUrl)) {
            throw new Exception('OIDC_PROVIDER_URL must be set to a valid URL');
        }
        if (empty($clientId) || !is_string($clientId)) {
            throw new Exception('OIDC_CLIENT_ID must be set to a valid string');
        }
        if (empty($clientSecret) || !is_string($clientSecret)) {
            throw new Exception('OIDC_CLIENT_SECRET must be set to a valid string');
        }
        
        self::$client = new OpenIDConnectClient($providerUrl, $clientId, $clientSecret);
        
        // Conditionally disable SSL verification based on environment
        $sslVerify = ApiKeys::get('OIDC_SSL_VERIFY');
        if ($sslVerify === 'false' || 
            (empty($sslVerify) && ApiKeys::get('APP_ENV') === 'development')) {
            self::$client->setVerifyHost(false);
            self::$client->setVerifyPeer(false);
        }
        
        // Set redirect URI
        $redirectUri = ApiKeys::getOidcRedirectUri();
        if (!empty($redirectUri) && is_string($redirectUri)) {
            self::$client->setRedirectURL($redirectUri);
        }
        
        // Set scopes
        $scopes = explode(' ', ApiKeys::getOidcScopes());
        $scopesArray = array_map('trim', $scopes);
        self::$client->addScope($scopesArray);
        
        return self::$client;
    }
    
    /**
     * Start OIDC authentication flow
     */
    public static function initiateAuth() {
        try {
            $client = self::initClient();
            $client->authenticate();
        } catch (Exception $e) {
            error_log('OIDC authentication error: ' . $e->getMessage());
            $_SESSION['oidc_error'] = 'OIDC configuration error: ' . $e->getMessage();
            
            // Clean any output buffer and redirect
            if (ob_get_level()) {
                ob_end_clean();
            }
            header('Location: index.php');
            exit;
        }
        
        return true;
    }
    
    /**
     * Handle OIDC callback and create/login user
     */
    public static function handleCallback() {
        try {
            $client = self::initClient();
            
            // Verify the authentication
            if (!$client->authenticate()) {
                error_log('OIDC: Authentication failed - client->authenticate() returned false');
                $_SESSION['oidc_error'] = 'OIDC authentication verification failed.';
                return false;
            }
            
            // Get user info from the OIDC provider
            $userInfo = $client->requestUserInfo();
            
            if (!$userInfo) {
                error_log('OIDC: Failed to get user info from provider');
                $_SESSION['oidc_error'] = 'Failed to retrieve user information from provider.';
                return false;
            }
            
            if (!isset($userInfo->email)) {
                error_log('OIDC: No email in user info. User info: ' . json_encode($userInfo));
                $_SESSION['oidc_error'] = 'No email address provided by the authentication provider.';
                return false;
            }
            
            // Get or create user in database
            $user = self::getOrCreateUser($userInfo);
            
            if ($user) {
                // Set session
                $_SESSION["USERPROFILE"] = $user;
                Frontend::$AIdetailArr["GMAIL"] = substr($user['BMAIL'], 0, strpos($user['BMAIL'], '@'));
                error_log('OIDC: Successfully authenticated user: ' . $user['BMAIL']);
                return true;
            } else {
                error_log('OIDC: Failed to create or retrieve user from database');
                $_SESSION['oidc_error'] = 'Failed to create or retrieve user account.';
                return false;
            }
            
        } catch (Exception $e) {
            error_log('OIDC callback error: ' . $e->getMessage());
            $_SESSION['oidc_error'] = 'OIDC callback error: ' . $e->getMessage();
        }
        
        return false;
    }
    
    /**
     * Get existing user or create new user from OIDC info
     */
    private static function getOrCreateUser($userInfo) {
        $email = DB::EscString($userInfo->email);
        $providerId = 'OIDC'; // Use simple 'OIDC' identifier instead of long subject
        
        // Try to find existing user by email first
        $uSQL = "SELECT * FROM BUSER WHERE BMAIL = '".$email."'";
        $uRes = DB::Query($uSQL);
        $uArr = DB::FetchArr($uRes);
        
        if ($uArr) {
            // Update existing user to mark as OIDC
            if ($uArr['BINTYPE'] !== 'OIDC') {
                $updateSQL = "UPDATE BUSER SET BPROVIDERID = '".$providerId."', BINTYPE = 'OIDC' WHERE BID = ".$uArr['BID'];
                DB::Query($updateSQL);
                $uArr['BPROVIDERID'] = $providerId;
                $uArr['BINTYPE'] = 'OIDC';
            }
            
            // Update user details from OIDC
            self::updateUserDetails($uArr['BID'], $userInfo);
            
            return $uArr;
        } else {
            // Create new user
            return self::createNewUser($userInfo, $email, $providerId);
        }
    }
    
    /**
     * Create new user from OIDC information
     */
    private static function createNewUser($userInfo, $email, $providerId) {
        $created = date('YmdHis');
        
        // Prepare user details JSON
        $userDetails = [
            'firstName' => isset($userInfo->given_name) ? $userInfo->given_name : '',
            'lastName' => isset($userInfo->family_name) ? $userInfo->family_name : '',
            'phone' => isset($userInfo->phone_number) ? $userInfo->phone_number : '',
            'companyName' => isset($userInfo->organization) ? $userInfo->organization : '',
            'vatId' => '',
            'street' => '',
            'zipCode' => '',
            'city' => '',
            'country' => '',
            'language' => isset($userInfo->locale) ? substr($userInfo->locale, 0, 2) : 'en',
            'timezone' => isset($userInfo->zoneinfo) ? $userInfo->zoneinfo : '',
            'invoiceEmail' => '',
            'oidc_subject' => isset($userInfo->sub) ? $userInfo->sub : '' // Store actual OIDC subject
        ];
        
        $userDetailsJson = json_encode($userDetails);
        $userDetailsEscaped = DB::EscString($userDetailsJson);
        
        // Insert new user
        $insertSQL = "INSERT INTO BUSER (BCREATED, BINTYPE, BMAIL, BPW, BPROVIDERID, BUSERLEVEL, BUSERDETAILS) 
                      VALUES ('".$created."', 'OIDC', '".$email."', '', '".$providerId."', 'NEW', '".$userDetailsEscaped."')";
        
        if (DB::Query($insertSQL)) {
            $userId = DB::LastId();
            
            // Fetch the new user record
            $newUserSQL = "SELECT * FROM BUSER WHERE BID = ".$userId;
            $newUserRes = DB::Query($newUserSQL);
            return DB::FetchArr($newUserRes);
        }
        
        return false;
    }
    
    /**
     * Update user details from OIDC information
     */
    private static function updateUserDetails($userId, $userInfo) {
        // Get current user details
        $userSQL = "SELECT BUSERDETAILS FROM BUSER WHERE BID = ".$userId;
        $userRes = DB::Query($userSQL);
        $userRow = DB::FetchArr($userRes);
        
        if ($userRow) {
            $currentDetails = json_decode($userRow['BUSERDETAILS'], true);
            if (!$currentDetails) {
                $currentDetails = [];
            }
            
            // Update with OIDC information (only if not already set)
            if (empty($currentDetails['firstName']) && isset($userInfo->given_name)) {
                $currentDetails['firstName'] = $userInfo->given_name;
            }
            if (empty($currentDetails['lastName']) && isset($userInfo->family_name)) {
                $currentDetails['lastName'] = $userInfo->family_name;
            }
            if (empty($currentDetails['phone']) && isset($userInfo->phone_number)) {
                $currentDetails['phone'] = $userInfo->phone_number;
            }
            if (empty($currentDetails['language']) && isset($userInfo->locale)) {
                $currentDetails['language'] = substr($userInfo->locale, 0, 2);
            }
            if (empty($currentDetails['timezone']) && isset($userInfo->zoneinfo)) {
                $currentDetails['timezone'] = $userInfo->zoneinfo;
            }
            
            $updatedDetailsJson = json_encode($currentDetails);
            $updatedDetailsEscaped = DB::EscString($updatedDetailsJson);
            
            $updateSQL = "UPDATE BUSER SET BUSERDETAILS = '".$updatedDetailsEscaped."' WHERE BID = ".$userId;
            DB::Query($updateSQL);
        }
    }
    
    /**
     * Check if OIDC is configured
     */
    public static function isConfigured() {
        return ApiKeys::getOidcProviderUrl() && 
               ApiKeys::getOidcClientId() && 
               ApiKeys::getOidcClientSecret();
    }

    /**
     * Check if auto-redirect to IDP is enabled
     */
    public static function isAutoRedirectEnabled() {
        $autoRedirect = ApiKeys::get('OIDC_AUTO_REDIRECT');
        // Default to true (enabled) unless explicitly set to 'false'
        return $autoRedirect !== 'false';
    }

    /**
     * Get OIDC login URL
     */
    public static function getLoginUrl() {
        if (!self::isConfigured()) {
            return null;
        }
        
        try {
            $client = self::initClient();
            return $client->getAuthorizationURL();
        } catch (Exception $e) {
            error_log('OIDC get login URL error: ' . $e->getMessage());
            return null;
        }
    }
}