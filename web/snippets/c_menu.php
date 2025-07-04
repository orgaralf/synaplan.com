<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block gradient-dots sidebar collapse">
    <div class="position-sticky pt-2">
        <!-- Mobile toggle button and logo -->
        <div class="px-3 py-2 mb-2 text-center">
            <a class="navbar-brand" href="/"><img src="img/synaplan_logo_ondark.svg" alt="AI management" width="140"></a>
            <button class="d-md-none collapsed btn btn-link text-white" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
        <hr style="border: 0; border-top: 1px solid #d1d5db; margin: 5px 10px 12px 10px;">
        <ul class="nav flex-column">
            <li class="nav-item">
            <a class="nav-link<?php ($contentInc=="chat") ? print " active" : ""; ?>" href="index.php/chat">
                <span data-feather="message-square"></span>
                Chat
            </a>
            </li>
            <li class="nav-item">
            <a class="nav-link<?php ($contentInc=="welcome") ? print " active" : ""; ?>" aria-current="page" href="index.php/welcome">
                <span data-feather="home"></span>
                Dashboard
            </a>
            </li>
            <li class="nav-item">
            <a class="nav-link<?php ($contentInc=="ais") ? print " active" : ""; ?>" href="index.php/ais">
                <span data-feather="cpu"></span>
                AI Config
            </a>
            <?php if($contentInc == "ais" || $contentInc == "inbound" || $contentInc == "preprocessor" 
                || $contentInc == "prompts" || $contentInc == "aimodels" || $contentInc == "outprocessor") { ?>
                <ul class="nav flex-column" style="margin-left: 18px;">
                <li class="subitem"><a href="index.php/inbound" id="menuPoint1">Inbound</a></li>
                <li class="subitem"><a href="index.php/aimodels" id="menuPoint4">AI Models</a></li>
                    <li class="subitem"><a href="index.php/prompts" id="menuPoint3">Task Prompts</a></li>
                    <li class="subitem"><a href="index.php/preprocessor" id="menuPoint2">Sorting Prompt</a></li>
                    <!-- li class="subitem"><a href="index.php/outprocessor">Outbound</a></li -->
                </ul>
            <?php } ?>
            </li>
            <li class="nav-item">
            <a class="nav-link<?php ($contentInc=="tools") ? print " active" : ""; ?>" href="index.php/tools">
                <span data-feather="tool"></span>
                MCP
            </a>
            </li>
            <li class="nav-item">
            <a class="nav-link<?php ($contentInc=="filemanager") ? print " active" : ""; ?>" href="index.php/filemanager">
                <span data-feather="image"></span>
                Files &amp; RAG
            </a>
            </li>
            <li class="nav-item">
            <a class="nav-link<?php ($contentInc=="settings") ? print " active" : ""; ?>" href="index.php/settings">
                <span data-feather="user"></span>
                Profile
            </a>
            </li>
        </ul>
    </div>
</nav>