// AI Icons for different providers

// Get AI Provider Icon from local SVG files
function getAIIcon(service, size = 16) {
  const cleanService = service.replace('AI', '').toLowerCase();
  
  const iconMap = {
    'openai': 'fa/svgs/brands/openai.svg',
    'anthropic': 'fa/svgs/brands/claude-color.svg', 
    'google': 'fa/svgs/brands/google-ai-1.svg',
    'groq': 'fa/svgs/brands/groq.svg',
    'ollama': 'fa/svgs/brands/ollama.svg',
    'deepseek': 'fa/svgs/brands/deepseek-color.svg'
  };
  
  if (iconMap[cleanService]) {
    // Return an img tag that loads the SVG file
    return `<img src="${iconMap[cleanService]}" alt="${cleanService}" width="${size}" height="${size}" style="display: inline-block; vertical-align: middle;">`;
  }
  
  // Fallback to FontAwesome
  return `<i class="fas fa-robot" style="font-size: ${size}px;"></i>`;
}


