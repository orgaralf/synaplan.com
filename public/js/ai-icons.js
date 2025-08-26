// AI Icons for different providers

// Get AI Provider Icon from local SVG files
function getAIIcon(service, size = 16) {
  // Robuste Service-Normalisierung
  if (!service) {
    return `<i class="fas fa-robot" style="font-size: ${size}px; color: #6c757d;"></i>`;
  }
  
  const cleanService = String(service).replace(/^AI/i, '').trim().toLowerCase();
  
  const iconMap = {
    'openai': 'fa/svgs/brands/openai.svg',
    'anthropic': 'fa/svgs/brands/claude-color.svg', 
    'google': 'fa/svgs/brands/google-ai-1.svg',
    'groq': 'fa/svgs/brands/groq.svg',
    'ollama': 'fa/svgs/brands/ollama.svg',
    'deepseek': 'fa/svgs/brands/deepseek-color.svg',
    'mistral': 'fa/svgs/brands/mistral-color.svg',
    'meta': 'fa/svgs/brands/groq.svg',  // Fallback zu Groq (häufiger Llama-Host)
    'meta-llama': 'fa/svgs/brands/groq.svg',  // Fallback zu Groq
    'llama': 'fa/svgs/brands/groq.svg',  // Fallback zu Groq
    'thehive': 'fa/svgs/brands/openai.svg'  // TheHive nutzt StabilityAI -> OpenAI Icon als Fallback
  };
  
  if (iconMap[cleanService]) {
    // Return an img tag that loads the SVG file
    return `<img src="${iconMap[cleanService]}" alt="${cleanService}" width="${size}" height="${size}" class="ai-icon">`;
  }
  
  // Fallback to FontAwesome Robot
  return `<i class="fas fa-robot" style="font-size: ${size}px; color: #6c757d;"></i>`;
}

// Prefer model-specific icons when we can detect them (e.g., DeepSeek via Groq)
// If model name suggests deepseek, show deepseek; otherwise fall back to service icon.
function getAIIconByModel(modelProviderOrName, service, size = 16) {
  const model = String(modelProviderOrName || '').toLowerCase();
  
  // Model-spezifische Erkennung (erweitert)
  if (model.includes('deepseek')) {
    return getAIIcon('deepseek', size);
  }
  if (model.includes('mistral')) {
    return getAIIcon('mistral', size);
  }
  if (model.includes('llama') || model.includes('meta-llama') || model.includes('meta')) {
    return getAIIcon('llama', size);
  }
  
  // Fallback-Logik verbessert: Wenn service unbekannt, aber model einen bekannten Vendor enthält
  if (!service || !String(service).trim()) {
    if (model.includes('gpt') || model.includes('openai')) {
      return getAIIcon('openai', size);
    }
    if (model.includes('claude') || model.includes('anthropic')) {
      return getAIIcon('anthropic', size);
    }
    if (model.includes('gemini') || model.includes('google')) {
      return getAIIcon('google', size);
    }
    if (model.includes('groq')) {
      return getAIIcon('groq', size);
    }
  }
  
  return getAIIcon(service, size);
}

// Konsolen-Diagnostik für Debug-Zwecke (nur wenn localStorage.debugIcons === '1')
function debugIconInfo(context, data) {
  if (typeof localStorage !== 'undefined' && localStorage.debugIcons === '1') {
    console.debug('AI Avatar Debug:', context, data);
  }
}

// Export für globale Verfügbarkeit
window.getAIIcon = getAIIcon;
window.getAIIconByModel = getAIIconByModel;
window.debugIconInfo = debugIconInfo;


