/* ===========================================    

  ACCESSIBILITY WIDGET

  github.com/sinanisler/accessibility-widgets

  github.com/sponsors/sinanisler

=========================================== */

// ===========================================
// TRANSLATIONS
// ===========================================

const TRANSLATIONS = {
  en: {
    accessibilityMenu: 'Accessibility Menu',
    closeAccessibilityMenu: 'Close Accessibility Menu',
    accessibilityTools: 'Accessibility Tools',
    resetAllSettings: 'Reset All Settings',
    screenReader: 'Screen Reader',
    voiceCommand: 'Voice Command',
    textSpacing: 'Text Spacing',
    pauseAnimations: 'Pause Animations',
    hideImages: 'Hide Images',
    dyslexiaFriendly: 'Dyslexia Friendly',
    biggerCursor: 'Bigger Cursor',
    lineHeight: 'Line Height',
    fontSelection: 'Font Selection',
    colorFilter: 'Color Filter',
    textAlign: 'Text Align',
    textSize: 'Text Size',
    highContrast: 'High Contrast',
    defaultFont: 'Default Font',
    noFilter: 'No Filter',
    default: 'Default',
    screenReaderOn: 'Screen reader on',
    screenReaderOff: 'Screen reader off',
    voiceControlActivated: 'Voice control activated',
    notSupportedBrowser: 'is not supported in this browser',
    close: 'Close',
    reset: 'Reset',
    saturation: 'Saturation',
    selectLanguage: 'Select Language'
  },
  de: {
    accessibilityMenu: 'Barrierefreiheitsmenü',
    closeAccessibilityMenu: 'Barrierefreiheitsmenü schließen',
    accessibilityTools: 'Barrierefreiheitswerkzeuge',
    resetAllSettings: 'Alle Einstellungen zurücksetzen',
    screenReader: 'Screenreader',
    voiceCommand: 'Sprachbefehl',
    textSpacing: 'Textabstand',
    pauseAnimations: 'Animationen pausieren',
    hideImages: 'Bilder ausblenden',
    dyslexiaFriendly: 'Legasthenie-freundlich',
    biggerCursor: 'Größerer Cursor',
    lineHeight: 'Zeilenhöhe',
    fontSelection: 'Schriftauswahl',
    colorFilter: 'Farbfilter',
    textAlign: 'Textausrichtung',
    textSize: 'Textgröße',
    highContrast: 'Hoher Kontrast',
    defaultFont: 'Standardschrift',
    noFilter: 'Kein Filter',
    default: 'Standard',
    screenReaderOn: 'Screenreader ein',
    screenReaderOff: 'Screenreader aus',
    voiceControlActivated: 'Sprachsteuerung aktiviert',
    notSupportedBrowser: 'wird in diesem Browser nicht unterstützt',
    close: 'Schließen',
    reset: 'Zurücksetzen',
    saturation: 'Sättigung',
    selectLanguage: 'Sprache wählen'
  },
  es: {
    accessibilityMenu: 'Menú de Accesibilidad',
    closeAccessibilityMenu: 'Cerrar Menú de Accesibilidad',
    accessibilityTools: 'Herramientas de Accesibilidad',
    resetAllSettings: 'Restablecer Todas las Configuraciones',
    screenReader: 'Lector de Pantalla',
    voiceCommand: 'Comando de Voz',
    textSpacing: 'Espaciado de Texto',
    pauseAnimations: 'Pausar Animaciones',
    hideImages: 'Ocultar Imágenes',
    dyslexiaFriendly: 'Amigable para Dislexia',
    biggerCursor: 'Cursor Más Grande',
    lineHeight: 'Altura de Línea',
    fontSelection: 'Selección de Fuente',
    colorFilter: 'Filtro de Color',
    textAlign: 'Alineación de Texto',
    textSize: 'Tamaño de Texto',
    highContrast: 'Alto Contraste',
    defaultFont: 'Fuente Predeterminada',
    noFilter: 'Sin Filtro',
    default: 'Predeterminado',
    screenReaderOn: 'Lector de pantalla activado',
    screenReaderOff: 'Lector de pantalla desactivado',
    voiceControlActivated: 'Control de voz activado',
    notSupportedBrowser: 'no es compatible con este navegador',
    close: 'Cerrar',
    reset: 'Restablecer',
    saturation: 'Saturación',
    selectLanguage: 'Seleccionar Idioma'
  },
  it: {
    accessibilityMenu: 'Menu Accessibilità',
    closeAccessibilityMenu: 'Chiudi Menu Accessibilità',
    accessibilityTools: 'Strumenti di Accessibilità',
    resetAllSettings: 'Ripristina Tutte le Impostazioni',
    screenReader: 'Lettore Schermo',
    voiceCommand: 'Comando Vocale',
    textSpacing: 'Spaziatura Testo',
    pauseAnimations: 'Pausa Animazioni',
    hideImages: 'Nascondi Immagini',
    dyslexiaFriendly: 'Adatto alla Dislessia',
    biggerCursor: 'Cursore Più Grande',
    lineHeight: 'Altezza Linea',
    fontSelection: 'Selezione Font',
    colorFilter: 'Filtro Colore',
    textAlign: 'Allineamento Testo',
    textSize: 'Dimensione Testo',
    highContrast: 'Alto Contrasto',
    defaultFont: 'Font Predefinito',
    noFilter: 'Nessun Filtro',
    default: 'Predefinito',
    screenReaderOn: 'Lettore schermo attivo',
    screenReaderOff: 'Lettore schermo disattivo',
    voiceControlActivated: 'Controllo vocale attivato',
    notSupportedBrowser: 'non è supportato in questo browser',
    close: 'Chiudi',
    reset: 'Ripristina',
    saturation: 'Saturazione',
    selectLanguage: 'Seleziona Lingua'
  },
  fr: {
    accessibilityMenu: 'Menu Accessibilité',
    closeAccessibilityMenu: 'Fermer le Menu Accessibilité',
    accessibilityTools: 'Outils d\'Accessibilité',
    resetAllSettings: 'Réinitialiser Tous les Paramètres',
    screenReader: 'Lecteur d\'Écran',
    voiceCommand: 'Commande Vocale',
    textSpacing: 'Espacement du Texte',
    pauseAnimations: 'Mettre en Pause les Animations',
    hideImages: 'Masquer les Images',
    dyslexiaFriendly: 'Convivial pour la Dyslexie',
    biggerCursor: 'Curseur Plus Grand',
    lineHeight: 'Hauteur de Ligne',
    fontSelection: 'Sélection de Police',
    colorFilter: 'Filtre de Couleur',
    textAlign: 'Alignement du Texte',
    textSize: 'Taille du Texte',
    highContrast: 'Contraste Élevé',
    defaultFont: 'Police par Défaut',
    noFilter: 'Aucun Filtre',
    default: 'Par Défaut',
    screenReaderOn: 'Lecteur d\'écran activé',
    screenReaderOff: 'Lecteur d\'écran désactivé',
    voiceControlActivated: 'Contrôle vocal activé',
    notSupportedBrowser: 'n\'est pas pris en charge dans ce navigateur',
    close: 'Fermer',
    reset: 'Réinitialiser',
    saturation: 'Saturation',
    selectLanguage: 'Sélectionner la Langue'
  },
  ru: {
    accessibilityMenu: 'Меню Доступности',
    closeAccessibilityMenu: 'Закрыть Меню Доступности',
    accessibilityTools: 'Инструменты Доступности',
    resetAllSettings: 'Сбросить Все Настройки',
    screenReader: 'Программа Чтения с Экрана',
    voiceCommand: 'Голосовая Команда',
    textSpacing: 'Межбуквенный Интервал',
    pauseAnimations: 'Приостановить Анимацию',
    hideImages: 'Скрыть Изображения',
    dyslexiaFriendly: 'Для Дислексии',
    biggerCursor: 'Увеличенный Курсор',
    lineHeight: 'Высота Строки',
    fontSelection: 'Выбор Шрифта',
    colorFilter: 'Цветовой Фильтр',
    textAlign: 'Выравнивание Текста',
    textSize: 'Размер Текста',
    highContrast: 'Высокая Контрастность',
    defaultFont: 'Шрифт по Умолчанию',
    noFilter: 'Без Фильтра',
    default: 'По Умолчанию',
    screenReaderOn: 'Программа чтения включена',
    screenReaderOff: 'Программа чтения выключена',
    voiceControlActivated: 'Голосовое управление активировано',
    notSupportedBrowser: 'не поддерживается в этом браузере',
    close: 'Закрыть',
    reset: 'Сбросить',
    saturation: 'Насыщенность',
    selectLanguage: 'Выберите Язык'
  },
  tr: {
    accessibilityMenu: 'Erişilebilirlik Menüsü',
    closeAccessibilityMenu: 'Erişilebilirlik Menüsünü Kapat',
    accessibilityTools: 'Erişilebilirlik Araçları',
    resetAllSettings: 'Tüm Ayarları Sıfırla',
    screenReader: 'Ekran Okuyucu',
    voiceCommand: 'Sesli Komut',
    textSpacing: 'Metin Aralığı',
    pauseAnimations: 'Animasyonları Duraklat',
    hideImages: 'Resimleri Gizle',
    dyslexiaFriendly: 'Disleksi Dostu',
    biggerCursor: 'Daha Büyük İmleç',
    lineHeight: 'Satır Yüksekliği',
    fontSelection: 'Yazı Tipi Seçimi',
    colorFilter: 'Renk Filtresi',
    textAlign: 'Metin Hizalama',
    textSize: 'Metin Boyutu',
    highContrast: 'Yüksek Kontrast',
    defaultFont: 'Varsayılan Yazı Tipi',
    noFilter: 'Filtre Yok',
    default: 'Varsayılan',
    screenReaderOn: 'Ekran okuyucu açık',
    screenReaderOff: 'Ekran okuyucu kapalı',
    voiceControlActivated: 'Sesli kontrol etkinleştirildi',
    notSupportedBrowser: 'bu tarayıcıda desteklenmiyor',
    close: 'Kapat',
    reset: 'Sıfırla',
    saturation: 'Doygunluk',
    selectLanguage: 'Dil Seçin'
  },
  ar: {
    accessibilityMenu: 'قائمة إمكانية الوصول',
    closeAccessibilityMenu: 'إغلاق قائمة إمكانية الوصول',
    accessibilityTools: 'أدوات إمكانية الوصول',
    resetAllSettings: 'إعادة تعيين جميع الإعدادات',
    screenReader: 'قارئ الشاشة',
    voiceCommand: 'الأمر الصوتي',
    textSpacing: 'تباعد النص',
    pauseAnimations: 'إيقاف الرسوم المتحركة مؤقتًا',
    hideImages: 'إخفاء الصور',
    dyslexiaFriendly: 'صديق لعسر القراءة',
    biggerCursor: 'مؤشر أكبر',
    lineHeight: 'ارتفاع الخط',
    fontSelection: 'اختيار الخط',
    colorFilter: 'مرشح الألوان',
    textAlign: 'محاذاة النص',
    textSize: 'حجم النص',
    highContrast: 'تباين عالي',
    defaultFont: 'الخط الافتراضي',
    noFilter: 'بدون مرشح',
    default: 'افتراضي',
    screenReaderOn: 'قارئ الشاشة مفعّل',
    screenReaderOff: 'قارئ الشاشة معطل',
    voiceControlActivated: 'تم تفعيل التحكم الصوتي',
    notSupportedBrowser: 'غير مدعوم في هذا المتصفح',
    close: 'إغلاق',
    reset: 'إعادة تعيين',
    saturation: 'التشبع',
    selectLanguage: 'اختر اللغة'
  },
  hi: {
    accessibilityMenu: 'पहुँच मेनू',
    closeAccessibilityMenu: 'पहुँच मेनू बंद करें',
    accessibilityTools: 'पहुँच उपकरण',
    resetAllSettings: 'सभी सेटिंग्स रीसेट करें',
    screenReader: 'स्क्रीन रीडर',
    voiceCommand: 'वॉयस कमांड',
    textSpacing: 'टेक्स्ट स्पेसिंग',
    pauseAnimations: 'एनिमेशन रोकें',
    hideImages: 'चित्र छिपाएँ',
    dyslexiaFriendly: 'डिस्लेक्सिया के अनुकूल',
    biggerCursor: 'बड़ा कर्सर',
    lineHeight: 'लाइन की ऊँचाई',
    fontSelection: 'फ़ॉन्ट चयन',
    colorFilter: 'रंग फ़िल्टर',
    textAlign: 'टेक्स्ट संरेखण',
    textSize: 'टेक्स्ट का आकार',
    highContrast: 'उच्च कंट्रास्ट',
    defaultFont: 'डिफ़ॉल्ट फ़ॉन्ट',
    noFilter: 'कोई फ़िल्टर नहीं',
    default: 'डिफ़ॉल्ट',
    screenReaderOn: 'स्क्रीन रीडर चालू',
    screenReaderOff: 'स्क्रीन रीडर बंद',
    voiceControlActivated: 'वॉयस नियंत्रण सक्रिय',
    notSupportedBrowser: 'इस ब्राउज़र में समर्थित नहीं है',
    close: 'बंद करें',
    reset: 'रीसेट करें',
    saturation: 'संतृप्ति',
    selectLanguage: 'भाषा चुनें'
  },
  'zh-cn': {
    accessibilityMenu: '辅助功能菜单',
    closeAccessibilityMenu: '关闭辅助功能菜单',
    accessibilityTools: '辅助功能工具',
    resetAllSettings: '重置所有设置',
    screenReader: '屏幕阅读器',
    voiceCommand: '语音命令',
    textSpacing: '文本间距',
    pauseAnimations: '暂停动画',
    hideImages: '隐藏图片',
    dyslexiaFriendly: '阅读障碍友好',
    biggerCursor: '更大的光标',
    lineHeight: '行高',
    fontSelection: '字体选择',
    colorFilter: '颜色滤镜',
    textAlign: '文本对齐',
    textSize: '文本大小',
    highContrast: '高对比度',
    defaultFont: '默认字体',
    noFilter: '无滤镜',
    default: '默认',
    screenReaderOn: '屏幕阅读器已开启',
    screenReaderOff: '屏幕阅读器已关闭',
    voiceControlActivated: '语音控制已激活',
    notSupportedBrowser: '此浏览器不支持',
    close: '关闭',
    reset: '重置',
    saturation: '饱和度',
    selectLanguage: '选择语言'
  },
  jp: {
    accessibilityMenu: 'アクセシビリティメニュー',
    closeAccessibilityMenu: 'アクセシビリティメニューを閉じる',
    accessibilityTools: 'アクセシビリティツール',
    resetAllSettings: 'すべての設定をリセット',
    screenReader: 'スクリーンリーダー',
    voiceCommand: '音声コマンド',
    textSpacing: 'テキスト間隔',
    pauseAnimations: 'アニメーション一時停止',
    hideImages: '画像を非表示',
    dyslexiaFriendly: 'ディスレクシア対応',
    biggerCursor: '大きいカーソル',
    lineHeight: '行の高さ',
    fontSelection: 'フォント選択',
    colorFilter: 'カラーフィルター',
    textAlign: 'テキスト配置',
    textSize: 'テキストサイズ',
    highContrast: 'ハイコントラスト',
    defaultFont: 'デフォルトフォント',
    noFilter: 'フィルターなし',
    default: 'デフォルト',
    screenReaderOn: 'スクリーンリーダーがオン',
    screenReaderOff: 'スクリーンリーダーがオフ',
    voiceControlActivated: '音声制御が有効',
    notSupportedBrowser: 'このブラウザではサポートされていません',
    close: '閉じる',
    reset: 'リセット',
    saturation: '彩度',
    selectLanguage: '言語を選択'
  }
};

// Language detection and management
let currentLanguage = 'en';

function detectBrowserLanguage() {
  const browserLang = (navigator.language || navigator.userLanguage).toLowerCase();
  
  // Direct match
  if (TRANSLATIONS[browserLang]) {
    return browserLang;
  }
  
  // Try language code only (e.g., 'en' from 'en-US')
  const langCode = browserLang.split('-')[0];
  if (TRANSLATIONS[langCode]) {
    return langCode;
  }
  
  // Special case for Chinese
  if (browserLang.includes('zh')) {
    if (browserLang.includes('cn') || browserLang.includes('hans')) {
      return 'zh-cn';
    }
  }
  
  // Default to English
  return 'en';
}

function setLanguage(lang) {
  if (TRANSLATIONS[lang]) {
    currentLanguage = lang;
    localStorage.setItem('accessibilityWidgetLanguage', lang);
    return true;
  }
  return false;
}

function getTranslation(key) {
  return TRANSLATIONS[currentLanguage][key] || TRANSLATIONS['en'][key] || key;
}

// Initialize language from localStorage or detect from browser
const savedLanguage = localStorage.getItem('accessibilityWidgetLanguage');
if (savedLanguage && TRANSLATIONS[savedLanguage]) {
  currentLanguage = savedLanguage;
} else {
  currentLanguage = detectBrowserLanguage();
  localStorage.setItem('accessibilityWidgetLanguage', currentLanguage);
}

// ===========================================
// CONFIGURATION VARIABLES
// ===========================================

// Default configuration - can be overridden by user
const DEFAULT_WIDGET_CONFIG = {
  // Core Features
  enableHighContrast: true,
  enableBiggerText: true,
  enableTextSpacing: true, // Now has 3 levels
  enablePauseAnimations: true, // Enhanced to include reduced motion features
  enableHideImages: true,
  enableDyslexiaFont: true,
  enableBiggerCursor: true,
  enableLineHeight: true, // Now has 3 levels (2em, 3em, 4em)
  enableTextAlign: true,

  // Advanced Features
  enableScreenReader: true,
  enableVoiceControl: true,
  enableFontSelection: true,
  enableColorFilter: true,

  // Widget Styling
  widgetWidth: '440px',
  widgetPosition: {
    side: 'right', // 'left' or 'right'
    right: '20px',
    left: '20px',
    bottom: '20px'
  },

  // Colors
  colors: {
    primary: '#1663d7',            // Header bg, main button bg, active border, close hover bg
    secondary: '#ffffff',         // Main button icon color
    optionBg: '#ffffff',         // Option button background
    optionText: '#333333',       // Option button text color
    optionIcon: '#000000'         // Option button icon color
  },

  // Button styling
  button: {
    size: '55px',
    borderRadius: '100px',
    iconSize: '40px',
    shadow: '0 4px 8px rgba(0, 0, 0, 0.2)'
  },

  // Menu styling
  menu: {
    headerHeight: '70px',
    padding: '0 10px 10px 10px',
    optionPadding: '20px 10px',
    optionMargin: '10px',
    borderRadius: '8px',
    fontSize: '16px',
    titleFontSize: '16px',
    closeButtonSize: '44px'
  },

  // Typography
  typography: {
    fontFamily: 'Arial, sans-serif',
    fontSize: '17px',
    titleFontSize: '22px',
    titleFontWeight: '700',
    lineHeight: '1'
  },

  // Animation
  animation: {
    transition: '0.2s',
    hoverScale: '1.05'
  },

  // Language/Text Configuration
  lang: {
    accessibilityMenu: 'Accessibility Menu',
    closeAccessibilityMenu: 'Close Accessibility Menu',
    accessibilityTools: 'Accessibility Tools',
    resetAllSettings: 'Reset All Settings',
    screenReader: 'Screen Reader',
    voiceCommand: 'Voice Command',
    textSpacing: 'Text Spacing',
    pauseAnimations: 'Pause Animations',
    hideImages: 'Hide Images',
    dyslexiaFriendly: 'Dyslexia Friendly',
    biggerCursor: 'Bigger Cursor',
    lineHeight: 'Line Height',
    fontSelection: 'Font Selection',
    colorFilter: 'Color Filter',
    textAlign: 'Text Align',
    textSize: 'Text Size',
    highContrast: 'High Contrast',
    defaultFont: 'Default Font',
    noFilter: 'No Filter',
    default: 'Default',
    screenReaderOn: 'Screen reader on',
    screenReaderOff: 'Screen reader off',
    voiceControlActivated: 'Voice control activated',
    notSupportedBrowser: 'is not supported in this browser',
    close: 'Close',
    reset: 'Reset'
  },

  // Voice Command Configuration - Developers can customize commands for different languages
  voiceCommands: {
    en: {
      showMenu: ['show menu', 'open menu', 'accessibility menu', 'access menu'],
      highContrast: ['high contrast', 'contrast', 'dark mode', 'increase contrast'],
      biggerText: ['bigger text', 'large text', 'text size', 'increase text', 'bigger', 'larger text', 'text bigger', 'make text bigger', 'enlarge text'],
      textSpacing: ['text spacing', 'spacing', 'letter spacing', 'text space'],
      pauseAnimations: ['pause animations', 'stop animations', 'disable animations', 'no animations'],
      hideImages: ['hide images', 'remove images', 'no images'],
      dyslexiaFont: ['dyslexia friendly', 'dyslexia font', 'readable font', 'easy font'],
      biggerCursor: ['bigger cursor', 'large cursor', 'cursor size', 'big cursor'],
      lineHeight: ['line height', 'line spacing', 'space between lines', 'line space'],
      textAlign: ['align text', 'text align', 'center text', 'alignment'],
      saturation: ['saturation', 'color saturation', 'saturate', 'desaturate', 'grayscale'],
      fontSelection: ['font selection', 'change font', 'select font', 'font'],
      colorFilter: ['color filter', 'color blind', 'colorblind', 'filter'],
      screenReader: ['screen reader', 'read aloud', 'voice reader'],
      voiceControl: ['voice command', 'voice control', 'voice commands'],
      resetAll: ['reset all', 'reset everything', 'clear all', 'reset settings', 'reset']
    },
    de: {
      showMenu: ['menü anzeigen', 'menü öffnen', 'barrierefreiheitsmenü', 'zugangsmenü'],
      highContrast: ['hoher kontrast', 'kontrast', 'dunkler modus', 'kontrast erhöhen'],
      biggerText: ['größerer text', 'großer text', 'textgröße', 'text vergrößern', 'größer', 'text größer'],
      textSpacing: ['textabstand', 'abstand', 'buchstabenabstand', 'text abstand'],
      pauseAnimations: ['animationen pausieren', 'animationen stoppen', 'animationen deaktivieren'],
      hideImages: ['bilder ausblenden', 'bilder entfernen', 'keine bilder'],
      dyslexiaFont: ['legasthenie freundlich', 'legasthenie schrift', 'lesbare schrift'],
      biggerCursor: ['größerer cursor', 'großer cursor', 'cursor größe'],
      lineHeight: ['zeilenhöhe', 'zeilenabstand', 'abstand zwischen zeilen'],
      textAlign: ['text ausrichten', 'textausrichtung', 'text zentrieren'],
      saturation: ['sättigung', 'farbsättigung', 'sättigen', 'entsättigen', 'graustufen'],
      fontSelection: ['schriftauswahl', 'schrift ändern', 'schrift wählen', 'schrift'],
      colorFilter: ['farbfilter', 'farbenblind', 'filter'],
      screenReader: ['screenreader', 'vorlesen', 'sprach reader'],
      voiceControl: ['sprachbefehl', 'sprachsteuerung', 'sprachbefehle'],
      resetAll: ['alles zurücksetzen', 'alle zurücksetzen', 'alle löschen', 'einstellungen zurücksetzen']
    },
    es: {
      showMenu: ['mostrar menú', 'abrir menú', 'menú de accesibilidad', 'menú de acceso'],
      highContrast: ['alto contraste', 'contraste', 'modo oscuro', 'aumentar contraste'],
      biggerText: ['texto más grande', 'texto grande', 'tamaño de texto', 'aumentar texto', 'más grande'],
      textSpacing: ['espaciado de texto', 'espaciado', 'espaciado de letras', 'espacio de texto'],
      pauseAnimations: ['pausar animaciones', 'detener animaciones', 'desactivar animaciones'],
      hideImages: ['ocultar imágenes', 'quitar imágenes', 'sin imágenes'],
      dyslexiaFont: ['amigable para dislexia', 'fuente de dislexia', 'fuente legible'],
      biggerCursor: ['cursor más grande', 'cursor grande', 'tamaño de cursor'],
      lineHeight: ['altura de línea', 'espaciado de líneas', 'espacio entre líneas'],
      textAlign: ['alinear texto', 'alineación de texto', 'centrar texto'],
      saturation: ['saturación', 'saturación de color', 'saturar', 'desaturar', 'escala de grises'],
      fontSelection: ['selección de fuente', 'cambiar fuente', 'seleccionar fuente', 'fuente'],
      colorFilter: ['filtro de color', 'daltónico', 'filtro'],
      screenReader: ['lector de pantalla', 'leer en voz alta', 'lector de voz'],
      voiceControl: ['comando de voz', 'control de voz', 'comandos de voz'],
      resetAll: ['restablecer todo', 'restablecer todo', 'borrar todo', 'restablecer configuración']
    },
    it: {
      showMenu: ['mostra menu', 'apri menu', 'menu accessibilità', 'menu accesso'],
      highContrast: ['alto contrasto', 'contrasto', 'modalità scura', 'aumenta contrasto'],
      biggerText: ['testo più grande', 'testo grande', 'dimensione testo', 'aumenta testo', 'più grande'],
      textSpacing: ['spaziatura testo', 'spaziatura', 'spaziatura lettere', 'spazio testo'],
      pauseAnimations: ['pausa animazioni', 'ferma animazioni', 'disabilita animazioni'],
      hideImages: ['nascondi immagini', 'rimuovi immagini', 'nessuna immagine'],
      dyslexiaFont: ['adatto alla dislessia', 'font dislessia', 'font leggibile'],
      biggerCursor: ['cursore più grande', 'cursore grande', 'dimensione cursore'],
      lineHeight: ['altezza linea', 'spaziatura linee', 'spazio tra linee'],
      textAlign: ['allinea testo', 'allineamento testo', 'centra testo'],
      saturation: ['saturazione', 'saturazione colore', 'saturare', 'desaturare', 'scala di grigi'],
      fontSelection: ['selezione font', 'cambia font', 'seleziona font', 'font'],
      colorFilter: ['filtro colore', 'daltonico', 'filtro'],
      screenReader: ['lettore schermo', 'leggi ad alta voce', 'lettore vocale'],
      voiceControl: ['comando vocale', 'controllo vocale', 'comandi vocali'],
      resetAll: ['ripristina tutto', 'ripristina tutto', 'cancella tutto', 'ripristina impostazioni']
    },
    fr: {
      showMenu: ['afficher menu', 'ouvrir menu', 'menu accessibilité', 'menu accès'],
      highContrast: ['contraste élevé', 'contraste', 'mode sombre', 'augmenter contraste'],
      biggerText: ['texte plus grand', 'grand texte', 'taille texte', 'augmenter texte', 'plus grand'],
      textSpacing: ['espacement texte', 'espacement', 'espacement lettres', 'espace texte'],
      pauseAnimations: ['mettre en pause animations', 'arrêter animations', 'désactiver animations'],
      hideImages: ['masquer images', 'supprimer images', 'aucune image'],
      dyslexiaFont: ['convivial dyslexie', 'police dyslexie', 'police lisible'],
      biggerCursor: ['curseur plus grand', 'grand curseur', 'taille curseur'],
      lineHeight: ['hauteur ligne', 'espacement lignes', 'espace entre lignes'],
      textAlign: ['aligner texte', 'alignement texte', 'centrer texte'],
      saturation: ['saturation', 'saturation couleur', 'saturer', 'désaturer', 'niveaux de gris'],
      fontSelection: ['sélection police', 'changer police', 'sélectionner police', 'police'],
      colorFilter: ['filtre couleur', 'daltonien', 'filtre'],
      screenReader: ['lecteur écran', 'lire à haute voix', 'lecteur vocal'],
      voiceControl: ['commande vocale', 'contrôle vocal', 'commandes vocales'],
      resetAll: ['réinitialiser tout', 'réinitialiser tout', 'effacer tout', 'réinitialiser paramètres']
    },
    ru: {
      showMenu: ['показать меню', 'открыть меню', 'меню доступности', 'меню доступа'],
      highContrast: ['высокая контрастность', 'контрастность', 'темный режим', 'увеличить контрастность'],
      biggerText: ['больший текст', 'большой текст', 'размер текста', 'увеличить текст', 'больше'],
      textSpacing: ['межбуквенный интервал', 'интервал', 'интервал букв', 'пространство текста'],
      pauseAnimations: ['приостановить анимацию', 'остановить анимацию', 'отключить анимацию'],
      hideImages: ['скрыть изображения', 'убрать изображения', 'без изображений'],
      dyslexiaFont: ['для дислексии', 'шрифт дислексии', 'читаемый шрифт'],
      biggerCursor: ['увеличенный курсор', 'большой курсор', 'размер курсора'],
      lineHeight: ['высота строки', 'интервал строк', 'пространство между строками'],
      textAlign: ['выровнять текст', 'выравнивание текста', 'центрировать текст'],
      saturation: ['насыщенность', 'насыщенность цвета', 'насытить', 'обесцветить', 'оттенки серого'],
      fontSelection: ['выбор шрифта', 'изменить шрифт', 'выбрать шрифт', 'шрифт'],
      colorFilter: ['цветовой фильтр', 'дальтонизм', 'фильтр'],
      screenReader: ['программа чтения', 'читать вслух', 'голосовой ридер'],
      voiceControl: ['голосовая команда', 'голосовое управление', 'голосовые команды'],
      resetAll: ['сбросить все', 'сбросить всё', 'очистить все', 'сбросить настройки']
    },
    tr: {
      showMenu: ['menüyü göster', 'menü aç', 'erişilebilirlik menüsü', 'erişim menüsü'],
      highContrast: ['yüksek kontrast', 'kontrast', 'karanlık mod', 'kontrastı artır'],
      biggerText: ['daha büyük metin', 'büyük metin', 'metin boyutu', 'metni büyüt', 'daha büyük'],
      textSpacing: ['metin aralığı', 'aralık', 'harf aralığı', 'metin boşluğu'],
      pauseAnimations: ['animasyonları duraklat', 'animasyonları durdur', 'animasyonları kapat'],
      hideImages: ['resimleri gizle', 'resimleri kaldır', 'resim yok'],
      dyslexiaFont: ['disleksi dostu', 'disleksi yazı tipi', 'okunabilir yazı tipi'],
      biggerCursor: ['daha büyük imleç', 'büyük imleç', 'imleç boyutu'],
      lineHeight: ['satır yüksekliği', 'satır aralığı', 'satırlar arası boşluk'],
      textAlign: ['metni hizala', 'metin hizalama', 'metni ortala'],
      saturation: ['doygunluk', 'renk doygunluğu', 'doygunlaştır', 'solgunlaştır', 'gri tonlama'],
      fontSelection: ['yazı tipi seçimi', 'yazı tipini değiştir', 'yazı tipi seç', 'yazı tipi'],
      colorFilter: ['renk filtresi', 'renk körü', 'filtre'],
      screenReader: ['ekran okuyucu', 'sesli oku', 'ses okuyucu'],
      voiceControl: ['sesli komut', 'sesli kontrol', 'sesli komutlar'],
      resetAll: ['hepsini sıfırla', 'tümünü sıfırla', 'hepsini temizle', 'ayarları sıfırla']
    },
    ar: {
      showMenu: ['إظهار القائمة', 'فتح القائمة', 'قائمة إمكانية الوصول', 'قائمة الوصول'],
      highContrast: ['تباين عالي', 'تباين', 'الوضع المظلم', 'زيادة التباين'],
      biggerText: ['نص أكبر', 'نص كبير', 'حجم النص', 'تكبير النص', 'أكبر'],
      textSpacing: ['تباعد النص', 'تباعد', 'تباعد الحروف', 'مساحة النص'],
      pauseAnimations: ['إيقاف الرسوم المتحركة مؤقتا', 'إيقاف الرسوم المتحركة', 'تعطيل الرسوم المتحركة'],
      hideImages: ['إخفاء الصور', 'إزالة الصور', 'بدون صور'],
      dyslexiaFont: ['صديق لعسر القراءة', 'خط عسر القراءة', 'خط قابل للقراءة'],
      biggerCursor: ['مؤشر أكبر', 'مؤشر كبير', 'حجم المؤشر'],
      lineHeight: ['ارتفاع الخط', 'تباعد الأسطر', 'مساحة بين الأسطر'],
      textAlign: ['محاذاة النص', 'محاذاة النص', 'توسيط النص'],
      saturation: ['التشبع', 'تشبع اللون', 'تشبيع', 'إزالة التشبع', 'تدرج رمادي'],
      fontSelection: ['اختيار الخط', 'تغيير الخط', 'اختر الخط', 'خط'],
      colorFilter: ['مرشح الألوان', 'عمى الألوان', 'مرشح'],
      screenReader: ['قارئ الشاشة', 'اقرأ بصوت عالٍ', 'قارئ صوتي'],
      voiceControl: ['الأمر الصوتي', 'التحكم الصوتي', 'الأوامر الصوتية'],
      resetAll: ['إعادة تعيين الكل', 'إعادة تعيين جميع', 'مسح الكل', 'إعادة تعيين الإعدادات']
    },
    hi: {
      showMenu: ['मेनू दिखाएं', 'मेनू खोलें', 'पहुंच मेनू', 'एक्सेस मेनू'],
      highContrast: ['उच्च कंट्रास्ट', 'कंट्रास्ट', 'डार्क मोड', 'कंट्रास्ट बढ़ाएं'],
      biggerText: ['बड़ा टेक्स्ट', 'बड़ा टेक्स्ट', 'टेक्स्ट का आकार', 'टेक्स्ट बढ़ाएं', 'बड़ा'],
      textSpacing: ['टेक्स्ट स्पेसिंग', 'स्पेसिंग', 'अक्षर स्पेसिंग', 'टेक्स्ट स्पेस'],
      pauseAnimations: ['एनिमेशन रोकें', 'एनिमेशन बंद करें', 'एनिमेशन अक्षम करें'],
      hideImages: ['चित्र छिपाएं', 'चित्र हटाएं', 'कोई चित्र नहीं'],
      dyslexiaFont: ['डिस्लेक्सिया के अनुकूल', 'डिस्लेक्सिया फ़ॉन्ट', 'पढ़ने योग्य फ़ॉन्ट'],
      biggerCursor: ['बड़ा कर्सर', 'बड़ा कर्सर', 'कर्सर का आकार'],
      lineHeight: ['लाइन की ऊंचाई', 'लाइन स्पेसिंग', 'लाइनों के बीच स्पेस'],
      textAlign: ['टेक्स्ट अलाइन करें', 'टेक्स्ट संरेखण', 'टेक्स्ट केंद्र में करें'],
      saturation: ['संतृप्ति', 'रंग संतृप्ति', 'संतृप्त करें', 'असंतृप्त करें', 'ग्रेस्केल'],
      fontSelection: ['फ़ॉन्ट चयन', 'फ़ॉन्ट बदलें', 'फ़ॉन्ट चुनें', 'फ़ॉन्ट'],
      colorFilter: ['रंग फ़िल्टर', 'वर्णान्धता', 'फ़िल्टर'],
      screenReader: ['स्क्रीन रीडर', 'जोर से पढ़ें', 'वॉयस रीडर'],
      voiceControl: ['वॉयस कमांड', 'वॉयस नियंत्रण', 'वॉयस कमांड्स'],
      resetAll: ['सभी रीसेट करें', 'सब कुछ रीसेट करें', 'सब साफ़ करें', 'सेटिंग्स रीसेट करें']
    },
    'zh-cn': {
      showMenu: ['显示菜单', '打开菜单', '辅助功能菜单', '访问菜单'],
      highContrast: ['高对比度', '对比度', '暗模式', '增加对比度'],
      biggerText: ['更大的文本', '大文本', '文本大小', '增大文本', '更大'],
      textSpacing: ['文本间距', '间距', '字母间距', '文本空间'],
      pauseAnimations: ['暂停动画', '停止动画', '禁用动画'],
      hideImages: ['隐藏图片', '删除图片', '无图片'],
      dyslexiaFont: ['阅读障碍友好', '阅读障碍字体', '可读字体'],
      biggerCursor: ['更大的光标', '大光标', '光标大小'],
      lineHeight: ['行高', '行间距', '行之间的空间'],
      textAlign: ['对齐文本', '文本对齐', '居中文本'],
      saturation: ['饱和度', '颜色饱和度', '饱和', '去饱和', '灰度'],
      fontSelection: ['字体选择', '更改字体', '选择字体', '字体'],
      colorFilter: ['颜色滤镜', '色盲', '滤镜'],
      screenReader: ['屏幕阅读器', '大声朗读', '语音阅读器'],
      voiceControl: ['语音命令', '语音控制', '语音命令'],
      resetAll: ['重置全部', '重置所有', '清除全部', '重置设置']
    },
    jp: {
      showMenu: ['メニューを表示', 'メニューを開く', 'アクセシビリティメニュー', 'アクセスメニュー'],
      highContrast: ['ハイコントラスト', 'コントラスト', 'ダークモード', 'コントラストを上げる'],
      biggerText: ['大きいテキスト', '大きなテキスト', 'テキストサイズ', 'テキストを大きく', 'より大きい'],
      textSpacing: ['テキスト間隔', '間隔', '文字間隔', 'テキストスペース'],
      pauseAnimations: ['アニメーション一時停止', 'アニメーション停止', 'アニメーション無効'],
      hideImages: ['画像を非表示', '画像を削除', '画像なし'],
      dyslexiaFont: ['ディスレクシア対応', 'ディスレクシアフォント', '読みやすいフォント'],
      biggerCursor: ['大きいカーソル', '大きなカーソル', 'カーソルサイズ'],
      lineHeight: ['行の高さ', '行間隔', '行間のスペース'],
      textAlign: ['テキスト配置', 'テキスト配置', 'テキストを中央'],
      saturation: ['彩度', '色彩度', '彩度を上げる', '彩度を下げる', 'グレースケール'],
      fontSelection: ['フォント選択', 'フォント変更', 'フォント選択', 'フォント'],
      colorFilter: ['カラーフィルター', '色覚異常', 'フィルター'],
      screenReader: ['スクリーンリーダー', '音声で読む', '音声リーダー'],
      voiceControl: ['音声コマンド', '音声制御', '音声コマンド'],
      resetAll: ['すべてリセット', 'すべてリセット', 'すべてクリア', '設定をリセット']
    }
  },

  // Grid Layout Configuration
  gridLayout: {
    columns: '1fr 1fr', // Default 2-column layout
    gap: '10px' // Gap between grid items
  }
};

// Function to deep merge user configuration with defaults
function mergeConfigs(defaultConfig, userConfig) {
  const result = { ...defaultConfig };

  if (!userConfig) return result;

  for (const key in userConfig) {
    if (userConfig.hasOwnProperty(key)) {
      if (typeof userConfig[key] === 'object' && userConfig[key] !== null && !Array.isArray(userConfig[key])) {
        result[key] = mergeConfigs(defaultConfig[key] || {}, userConfig[key]);
      } else {
        result[key] = userConfig[key];
      }
    }
  }

  return result;
}

// Merge user configuration with defaults
const WIDGET_CONFIG = mergeConfigs(DEFAULT_WIDGET_CONFIG, window.ACCESSIBILITY_WIDGET_CONFIG || {});

// ===========================================
// STYLES & VISUAL ASSETS
// ===========================================

// Widget styles (will go inside Shadow DOM - NOT affected by page styles or accessibility features)
const widgetStyles = `
  :host {
    all: initial;
    font-family: ${WIDGET_CONFIG.typography.fontFamily};
  }
  
  * {
    box-sizing: border-box;
  }
  
  #snn-accessibility-fixed-button {
    position: fixed !important;
    ${WIDGET_CONFIG.widgetPosition.side}: ${WIDGET_CONFIG.widgetPosition[WIDGET_CONFIG.widgetPosition.side]} !important;
    bottom: ${WIDGET_CONFIG.widgetPosition.bottom} !important;
    z-index: 9999;
    background:${WIDGET_CONFIG.colors.primary};
    padding:5px;
    border-radius:100%;
  }
  
  #snn-accessibility-button {
    background: ${WIDGET_CONFIG.colors.primary};
    border: none;
    border-radius: ${WIDGET_CONFIG.button.borderRadius};
    cursor: pointer;
    width: ${WIDGET_CONFIG.button.size};
    height: ${WIDGET_CONFIG.button.size};
    box-shadow: ${WIDGET_CONFIG.button.shadow};
    transition: ${WIDGET_CONFIG.animation.transition} !important;
    display: flex;
    justify-content: center;
    align-items: center;
    border:solid 4px white;
  }
  
  #snn-accessibility-button:hover {
    transform: scale(${WIDGET_CONFIG.animation.hoverScale});
  }
  
  #snn-accessibility-button:focus {
    outline: 2px solid ${WIDGET_CONFIG.colors.secondary};
    outline-offset: 2px;
  }
  
  #snn-accessibility-button svg {
    width: ${WIDGET_CONFIG.button.iconSize};
    height: ${WIDGET_CONFIG.button.iconSize};
    fill: ${WIDGET_CONFIG.colors.secondary};
    pointer-events: none;
  }
  
  #snn-accessibility-menu {
    position: fixed;
    top: 0;
    ${WIDGET_CONFIG.widgetPosition.side}: 0;
    max-width: ${WIDGET_CONFIG.widgetWidth};
    width:100%;
    height: 100vh;
    overflow-y: auto;
    background-color: #e2e2e2;
    padding: 0;
    display: none;
    font-family: ${WIDGET_CONFIG.typography.fontFamily};
    z-index: 999999;
    scrollbar-width: thin;
    line-height:1 !important;
  }
  
  .snn-accessibility-option {
    font-size: ${WIDGET_CONFIG.menu.fontSize};
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-around;
    padding: 5px;
    width: 100%;
    background-color: ${WIDGET_CONFIG.colors.optionBg};
    color: ${WIDGET_CONFIG.colors.optionText};
    border: 3px solid ${WIDGET_CONFIG.colors.optionBg};
    cursor: pointer;
    border-radius: ${WIDGET_CONFIG.menu.borderRadius};
    transition: background-color ${WIDGET_CONFIG.animation.transition}, border-color ${WIDGET_CONFIG.animation.transition};
    line-height: ${WIDGET_CONFIG.typography.lineHeight} !important;
    gap: 5px;
    min-height: 105px;
  }
  
  .snn-accessibility-option:hover {
    border-color: ${WIDGET_CONFIG.colors.primary};
  }
  
  .snn-accessibility-option.active {
    border-color: ${WIDGET_CONFIG.colors.primary};
  }
  
  .snn-accessibility-option:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }
  
  .snn-icon {
    width: ${WIDGET_CONFIG.button.iconSize};
    height: ${WIDGET_CONFIG.button.iconSize};
    fill: ${WIDGET_CONFIG.colors.optionIcon};
    flex-shrink: 0;
  }
  
  .snn-icon svg {
    width: 100%;
    height: 100%;
    fill: currentColor;
  }
  
  .snn-button-text {
    text-align: center;
    line-height: 1.2;
    font-size:16px;
    font-weight: 600;
  }
  
  .snn-option-steps {
    display: flex;
    gap: 5px;
    align-items: center;
    justify-content: center;
    margin-top: 5px;
  }
  
  .snn-option-step {
    width: 30px;
    height: 6px;
    border-radius: 3px;
    background-color: #d0d0d0;
    transition: background-color ${WIDGET_CONFIG.animation.transition};
  }
  
  .snn-option-step.active {
    background-color: ${WIDGET_CONFIG.colors.primary};
  }
  
  .snn-close, .snn-reset-button {
    background: none;
    border: none;
    font-size: ${WIDGET_CONFIG.menu.closeButtonSize};
    color: ${WIDGET_CONFIG.colors.secondary};
    cursor: pointer;
    line-height: ${WIDGET_CONFIG.typography.lineHeight};
    border-radius: ${WIDGET_CONFIG.button.borderRadius};
    width: ${WIDGET_CONFIG.menu.closeButtonSize};
    height: ${WIDGET_CONFIG.menu.closeButtonSize};
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .snn-close::before {
    content: '×';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: ${WIDGET_CONFIG.menu.closeButtonSize};
    line-height: 1;
  }
  
  .snn-reset-button svg {
    width: 22px;
    height: 22px;
    fill: ${WIDGET_CONFIG.colors.secondary};
  }
  
  .snn-close:focus, .snn-reset-button:focus {
    outline: solid 2px ${WIDGET_CONFIG.colors.secondary};
  }
  
  .snn-close:hover, .snn-reset-button:hover {
    color: ${WIDGET_CONFIG.colors.secondary};
    background: rgba(255, 255, 255, 0.2);
  }
  
  /* Tooltip styles */
  .snn-tooltip {
    position: absolute;
    bottom: -35px;
    left: 50%;
    transform: translateX(-50%);
    background-color: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 6px 10px;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.2s;
    z-index: 1000;
  }
  
  .snn-tooltip::before {
    content: '';
    position: absolute;
    top: -4px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 5px solid transparent;
    border-right: 5px solid transparent;
    border-bottom: 5px solid rgba(0, 0, 0, 0.8);
  }
  
  .snn-close:hover .snn-tooltip,
  .snn-close:focus .snn-tooltip,
  .snn-reset-button:hover .snn-tooltip,
  .snn-reset-button:focus .snn-tooltip {
    opacity: 1;
  }
  
  .snn-header {
    display: flex;
    align-items: center;
    padding: 10px;
    background: ${WIDGET_CONFIG.colors.primary};
    height: ${WIDGET_CONFIG.menu.headerHeight};
    position: sticky;
    top: 0;
    z-index: 10;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    gap: 8px;
  }
  
  .snn-content {
    padding: 20px 20px 0px 20px;
  }
  
  .snn-language-selector {
    width: 100%;
    background: white;
    color: black;
    border: none;
    padding: 14px;
    font-size: 16px;
    font-family: ${WIDGET_CONFIG.typography.fontFamily};
    border-radius: 5px;
    margin-bottom: 20px;
    cursor: pointer;
    outline: none;
  }
  
  .snn-language-selector:focus {
    outline: 2px solid ${WIDGET_CONFIG.colors.primary};
    outline-offset: 2px;
  }
  
  .snn-options-grid {
    display: grid;
    grid-template-columns: ${WIDGET_CONFIG.gridLayout.columns};
    gap: ${WIDGET_CONFIG.gridLayout.gap};
    margin-bottom: 20px;
  }
  
  .snn-title {
    margin: 0;
    font-size: ${WIDGET_CONFIG.menu.titleFontSize};
    color: ${WIDGET_CONFIG.colors.secondary};
    line-height: ${WIDGET_CONFIG.typography.lineHeight} !important;
    margin-left: 5px;
    font-weight: ${WIDGET_CONFIG.typography.titleFontWeight};
    flex: 1;
    letter-spacing: 1px !important;
    word-spacing: 2px !important;
    text-align: left;
  }
`;

// Page accessibility styles (will go in main document - these affect the page, NOT the widget)
const pageStyles = `
  /* High Contrast Modes */
  .snn-high-contrast-medium {
    filter: none !important;
  }
  .snn-high-contrast-medium *:not(#snn-accessibility-widget-container):not(#snn-accessibility-widget-container *) {
    filter: contrast(1.3) !important;
  }
  
  .snn-high-contrast-high {
    background-color: #000 !important;
    color: #fff !important;
    filter: none !important;
  }
  .snn-high-contrast-high *:not(#snn-accessibility-widget-container):not(#snn-accessibility-widget-container *) {
    background-color: #000 !important;
    color: #fff !important;
    filter: contrast(1.5) !important;
  }
  
  .snn-high-contrast-ultra {
    background-color: #000 !important;
    color: #ffff00 !important;
    filter: none !important;
  }
  .snn-high-contrast-ultra *:not(#snn-accessibility-widget-container):not(#snn-accessibility-widget-container *) {
    background-color: #000 !important;
    color: #ffff00 !important;
    filter: contrast(2.0) !important;
  }
  
  /* Text Size */
  .snn-bigger-text-medium * {
    font-size: 20px !important;
  }
  .snn-bigger-text-large * {
    font-size: 24px !important;
  }
  .snn-bigger-text-xlarge * {
    font-size: 28px !important;
  }
  
  /* Text Spacing - 3 Options */
  .snn-text-spacing-light * {
    letter-spacing: 0.1em !important;
    word-spacing: 0.5em !important;
  }
  .snn-text-spacing-medium * {
    letter-spacing: 0.15em !important;
    word-spacing: 1em !important;
  }
  .snn-text-spacing-heavy * {
    letter-spacing: 0.25em !important;
    word-spacing: 2em !important;
  }
  
  /* Pause Animations (Enhanced to include Reduced Motion features) */
  .snn-pause-animations * {
    animation: none !important;
    transition: none !important;
  }
  .snn-pause-animations *::before {
    animation: none !important;
    transition: none !important;
  }
  .snn-pause-animations *::after {
    animation: none !important;
    transition: none !important;
  }
  
  /* Dyslexia Font */
  .snn-dyslexia-font {
    font-family: 'Comic Sans MS', 'Chalkboard SE', 'Bradley Hand', 'Brush Script MT', fantasy !important;
  }
  .snn-dyslexia-font * {
    font-family: 'Comic Sans MS', 'Chalkboard SE', 'Bradley Hand', 'Brush Script MT', fantasy !important;
  }
  
  /* Line Height - 3 Options */
  .snn-line-height-2em * {
    line-height: 2 !important;
  }
  .snn-line-height-3em * {
    line-height: 3 !important;
  }
  .snn-line-height-4em * {
    line-height: 4 !important;
  }
  
  /* Text Alignment */
  .snn-text-align-left * {
    text-align: left !important;
  }
  .snn-text-align-center * {
    text-align: center !important;
  }
  .snn-text-align-right * {
    text-align: right !important;
  }
  
  /* Bigger Cursor */
  .snn-bigger-cursor {
    cursor: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDgiIGhlaWdodD0iNzIiIHZpZXdCb3g9IjAgMCA0OCA3MiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cGF0aCBkPSJNNCAyVjcwTDIwIDU0SDM2TDQgMloiIGZpbGw9IiMwMDAiIHN0cm9rZT0iI2ZmZiIgc3Ryb2tlLXdpZHRoPSI0Ii8+PC9zdmc+'), auto !important;
  }
  .snn-bigger-cursor * {
    cursor: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDgiIGhlaWdodD0iNzIiIHZpZXdCb3g9IjAgMCA0OCA3MiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cGF0aCBkPSJNNCAyVjcwTDIwIDU0SDM2TDQgMloiIGZpbGw9IiMwMDAiIHN0cm9rZT0iI2ZmZiIgc3Ryb2tlLXdpZHRoPSI0Ii8+PC9zdmc+'), auto !important;
  }
  
  /* Font Selection */
  .snn-font-arial {
    font-family: Arial, sans-serif !important;
  }
  .snn-font-arial * {
    font-family: Arial, sans-serif !important;
  }
  .snn-font-times {
    font-family: 'Times New Roman', serif !important;
  }
  .snn-font-times * {
    font-family: 'Times New Roman', serif !important;
  }
  .snn-font-verdana {
    font-family: Verdana, sans-serif !important;
  }
  .snn-font-verdana * {
    font-family: Verdana, sans-serif !important;
  }
  
  /* Color Filters */
  .snn-filter-protanopia {
    filter: none !important;
  }
  .snn-filter-protanopia body > *:not(#snn-accessibility-widget-container) {
    filter: url('#protanopia-filter') !important;
  }
  .snn-filter-deuteranopia {
    filter: none !important;
  }
  .snn-filter-deuteranopia body > *:not(#snn-accessibility-widget-container) {
    filter: url('#deuteranopia-filter') !important;
  }
  .snn-filter-tritanopia {
    filter: none !important;
  }
  .snn-filter-tritanopia body > *:not(#snn-accessibility-widget-container) {
    filter: url('#tritanopia-filter') !important;
  }
  .snn-filter-grayscale {
    filter: none !important;
  }
  .snn-filter-grayscale body > *:not(#snn-accessibility-widget-container) {
    filter: grayscale(100%) !important;
  }
  
  /* Saturation Filters */
  .snn-saturation-low {
    filter: none !important;
  }
  .snn-saturation-low body > *:not(#snn-accessibility-widget-container) {
    filter: saturate(0.5) !important;
  }
  .snn-saturation-high {
    filter: none !important;
  }
  .snn-saturation-high body > *:not(#snn-accessibility-widget-container) {
    filter: saturate(10) !important;
  }
  .snn-saturation-none {
    filter: none !important;
  }
  .snn-saturation-none body > *:not(#snn-accessibility-widget-container) {
    filter: grayscale(100%) saturate(0) !important;
  }
  
  /* Protect widget container from page styles */
  #snn-accessibility-widget-container,
  #snn-accessibility-widget-container * {
    filter: none !important;
    background-color: initial !important;
    color: initial !important;
  }
`;

// ===========================================
// SVG ICONS
// ===========================================

const icons = {
  buttonsvg: `<svg xmlns="http://www.w3.org/2000/svg" style="fill:white;" viewBox="0 0 24 24" width="30px" height="30px"><path d="M0 0h24v24H0V0z" fill="none"></path><path d="M20.5 6c-2.61 0.7-5.67 1-8.5 1s-5.89-0.3-8.5-1L3 8c1.86 0.5 4 0.83 6 1v13h2v-6h2v6h2V9c2-0.17 4.14-0.5 6-1l-0.5-2zM12 6c1.1 0 2-0.9 2-2s-0.9-2-2-2-2 0.9-2 2 0.9 2 2 2z"></path></svg>`,
  highContrast: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" version="1.2" viewBox="0 0 35 35"><path fill="currentColor" fill-rule="evenodd" d="M1.89998 15.6285c0-7.58203 6.14649-13.72852 13.72852-13.72852 7.5821 0 13.7286 6.14649 13.7286 13.72852 0 0.6081-0.0395 1.2069-0.1161 1.794 0.5933 0.2913 1.1478 0.6497 1.6534 1.0654 0.1725-0.9268 0.2627-1.8825 0.2627-2.8594 0-8.57615-6.9524-15.5285244-15.5286-15.5285244C7.05235 0.0999756 0.0999756 7.05235 0.0999756 15.6285c0 8.5762 6.9523744 15.5286 15.5285244 15.5286 1.2241 0 2.415-0.1416 3.5574-0.4093-0.4388-0.4866-0.8222-1.0242-1.1402-1.6028-0.7847 0.1394-1.5924 0.2121-2.4172 0.2121-7.58203 0-13.72852-6.1465-13.72852-13.7286Z" clip-rule="evenodd"/><path fill="currentColor" fill-rule="evenodd" d="M2.35 15.6286C2.35 8.29502 8.29502 2.35 15.6286 2.35c7.3335 0 13.2785 5.94502 13.2785 13.2786 0 0.5408-0.0323 1.0741-0.0951 1.5979 0.444 0.1881 0.8687 0.4128 1.2703 0.6703 0.1151-0.7392 0.1748-1.4967 0.1748-2.2682C30.2571 7.54943 23.7077 1 15.6286 1 7.54943 1 1 7.54943 1 15.6286c0 8.0791 6.54943 14.6285 14.6286 14.6285 1.0033 0 1.9831-0.101 2.9297-0.2934-0.276-0.3898-0.52-0.8038-0.7282-1.2382-0.716 0.1195-1.4515 0.1816-2.2015 0.1816-7.33358 0-13.2786-5.945-13.2786-13.2785Z" clip-rule="evenodd"/><path fill="currentColor" fill-rule="evenodd" d="M15.6286 1C7.54943 1 1 7.54943 1 15.6286c0 8.0791 6.54943 14.6285 14.6286 14.6285" clip-rule="evenodd"/><path stroke="currentColor" stroke-width="1.8" d="M15.6286 1C7.54943 1 1 7.54943 1 15.6286c0 8.0791 6.54943 14.6285 14.6286 14.6285"/><path fill="currentColor" fill-rule="evenodd" d="M22.8729 25.114c0-1.3811 1.0901-2.5007 2.4359-2.5007 1.3459 0 2.436 1.1196 2.436 2.5007 0 1.38-1.0901 2.4997-2.436 2.4997-1.3458 0-2.4359-1.1197-2.4359-2.4997Zm7.2258-2.0373c-0.0899-0.2248-0.071-0.4785 0.0512-0.6875l0.912-1.5598c0.0898-0.1532 0.0668-0.3504-0.0574-0.4779l-1.0556-1.0832c-0.1232-0.1264-0.3153-0.1511-0.4657-0.0589l-1.5225 0.9374c-0.201 0.1237-0.4495 0.1427-0.667 0.051-0.2181-0.092-0.3797-0.2819-0.4358-0.5118l-0.4329-1.7763c-0.0428-0.1735-0.1953-0.2957-0.3696-0.2957h-1.4931c-0.1744 0-0.3268 0.1222-0.3696 0.2957l-0.433 1.7763c-0.056 0.2299-0.2177 0.4198-0.4357 0.5118-0.2176 0.0917-0.466 0.0727-0.6671-0.051l-1.5225-0.9374c-0.1503-0.0922-0.3424-0.0675-0.4656 0.0589l-1.0556 1.0832c-0.1243 0.1275-0.1473 0.3247-0.0575 0.4779l0.9121 1.5598c0.1222 0.209 0.1411 0.4627 0.0511 0.6875-0.0895 0.2239-0.2806 0.3916-0.5142 0.4514l-1.7165 0.4395c-0.1692 0.0439-0.2882 0.2003-0.2882 0.3803v1.5311c0 0.18 0.119 0.3364 0.2882 0.3804l1.7165 0.4394c0.2336 0.0599 0.4247 0.2276 0.5142 0.4515 0.09 0.2247 0.0711 0.4785-0.0511 0.6874l-0.9121 1.5599c-0.0898 0.1532-0.0668 0.3503 0.0575 0.4778l1.0556 1.0833c0.1232 0.1264 0.3153 0.151 0.4656 0.0589l1.5225-0.9374c0.2011-0.1238 0.4495-0.1428 0.6671-0.051 0.218 0.092 0.3797 0.2818 0.4357 0.5118l0.433 1.7762c0.0428 0.1736 0.1952 0.2968 0.3696 0.2968h1.4931c0.1743 0 0.3268-0.1232 0.3696-0.2968l0.4329-1.7762c0.0561-0.23 0.2177-0.4198 0.4358-0.5118 0.2175-0.0918 0.466-0.0728 0.667 0.051l1.5225 0.9374c0.1504 0.0921 0.3425 0.0675 0.4657-0.0589l1.0556-1.0833c0.1242-0.1275 0.1472-0.3246 0.0574-0.4778l-0.912-1.5599c-0.1222-0.2089-0.1411-0.4627-0.0512-0.6874 0.0896-0.2239 0.2806-0.3916 0.5142-0.4515l1.7166-0.4394c0.1691-0.044 0.2881-0.2004 0.2881-0.3804v-1.5311c0-0.18-0.119-0.3364-0.2881-0.3803l-1.7166-0.4395c-0.2336-0.0598-0.4246-0.2275-0.5142-0.4514Z" clip-rule="evenodd"/></svg>`,
  biggerText: `<svg xmlns="http://www.w3.org/2000/svg" version="1.2" viewBox="0 0 36 23"><g fill="none" fill-rule="evenodd" stroke="currentColor" stroke-linecap="round" stroke-width="2"><path stroke-linejoin="round" d="M26.58 21.3225806V1m-7.92 4.06451613V1H34.5v4.06451613"/><path d="M22.62 21.3225806h7.92"/><path stroke-linejoin="round" d="M6.78 18.6129032V5.06451613M1.5 7.77419355V5.06451613h10.56v2.70967742"/><path d="M4.14 18.6129032h5.28"/></g></svg>`,
  textSpacing: `<svg xmlns="http://www.w3.org/2000/svg" width="800px" height="800px" viewBox="0 0 15 15" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M4.55293 0.999969C4.75295 0.999969 4.93372 1.11917 5.0125 1.30301L8.01106 8.29982C8.11984 8.55363 8.00226 8.84757 7.74844 8.95635C7.49463 9.06512 7.20069 8.94754 7.09191 8.69373L6.11613 6.41685H2.98973L2.01395 8.69373C1.90517 8.94754 1.61123 9.06512 1.35742 8.95635C1.1036 8.84757 0.986023 8.55363 1.0948 8.29982L4.09336 1.30301C4.17214 1.11917 4.35291 0.999969 4.55293 0.999969ZM4.55293 2.76929L5.75186 5.56685H3.354L4.55293 2.76929ZM11.0562 9.00214C11.2617 9.00214 11.4463 8.87633 11.5215 8.68502L14.2733 1.68299C14.3743 1.42598 14.2478 1.13575 13.9908 1.03475C13.7338 0.933747 13.4436 1.06021 13.3426 1.31722L11.0562 7.13514L8.76973 1.31722C8.66873 1.06021 8.3785 0.933747 8.1215 1.03475C7.86449 1.13575 7.73802 1.42598 7.83902 1.68299L10.5908 8.68502C10.666 8.87633 10.8506 9.00214 11.0562 9.00214ZM14.9537 12.4999C14.9537 12.606 14.9115 12.7077 14.8365 12.7828L12.8365 14.7828C12.6803 14.939 12.4271 14.939 12.2708 14.7828C12.1146 14.6265 12.1146 14.3733 12.2708 14.2171L13.588 12.8999H1.51937L2.83653 14.2171C2.99274 14.3733 2.99274 14.6265 2.83653 14.7828C2.68032 14.939 2.42705 14.939 2.27084 14.7828L0.270843 12.7828C0.195828 12.7077 0.153687 12.606 0.153687 12.4999C0.153687 12.3938 0.195828 12.2921 0.270843 12.2171L2.27084 10.2171C2.42705 10.0609 2.68032 10.0609 2.83653 10.2171C2.99274 10.3733 2.99274 10.6265 2.83653 10.7828L1.51937 12.0999L13.588 12.0999L12.2708 10.7828C12.1146 10.6265 12.1146 10.3733 12.2708 10.2171C12.4271 10.0609 12.6803 10.0609 12.8365 10.2171L14.8365 12.2171C14.9115 12.2921 14.9537 12.3938 14.9537 12.4999Z" fill="#000000"/></svg>`,
  pauseAnimations: `<svg xmlns="http://www.w3.org/2000/svg" version="1.2" viewBox="0 0 37 36"><g fill="none" fill-rule="evenodd"><path fill="currentColor" d="M15.8087111 23.6666667h-1.2702778c-.4429444 0-.8018333-.3598334-.8018333-.8027778v-9.7277778c0-.4429444.3588889-.8027778.8018333-.8027778h1.2702778c.4429445 0 .8027778.3598334.8027778.8027778v9.7277778c0 .4429444-.3598333.8027778-.8027778.8027778m6.6525722 0h-1.2702777c-.442 0-.8018334-.3598334-.8018334-.8027778v-9.7277778c0-.4429444.3598334-.8027778.8018334-.8027778h1.2702777c.4438889 0 .8027778.3598334.8027778.8027778v9.7277778c0 .4429444-.3588889.8027778-.8027778.8027778"/><path stroke="currentColor" stroke-linecap="round" stroke-width="1.88888889" d="M18.5 4.77777778V1m0 34v-3.7777778M31.7222222 18H35.5m-34 0h3.77777778m3.87278889-9.34943333L6.47873333 5.97967778M30.5204167 30.0204167l-2.6708889-2.6708889m-.0000945-18.69896113 2.6708889-2.67088889M6.47911111 30.0204167l2.67183333-2.6708889M23.5542889 5.78219444l1.4440555-3.49066666M12.0013722 33.7087556l1.4440556-3.4906667m17.2723778-7.1638 3.4906666 1.4440555M2.79124444 11.5013722l3.49066667 1.4440556m7.15274999-7.15860558L11.9877722 2.2971m13.0246445 31.4061778-1.4468889-3.4897222m7.14765-17.2788945L34.2029 11.4877722M2.79672222 24.5124167l3.48972222-1.4468889"/></g></svg>`,
  hideImages: `<svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><path d="M32 12C16 12 4 32 4 32s12 20 28 20 28-20 28-20S48 12 32 12zm0 32a12 12 0 1112-12 12 12 0 01-12 12z"/><circle cx="32" cy="32" r="8"/></svg>`,
  dyslexiaFont: `<svg xmlns="http://www.w3.org/2000/svg" version="1.2" viewBox="0 0 31 22"><path fill="currentColor" fill-rule="evenodd" d="M.5 22V1.0083333h7.2421899c6.8051611 0 11.6124768 4.3388889 11.6124768 10.4805556C19.3546667 17.6305556 14.547351 22 7.7421899 22H.5Zm2.4348742-4.31h4.8073157c5.3692097 0 9.1463863-2.8616703 9.1463863-7.27 0-4.3807776-3.7771766-7.2422222-9.1463863-7.2422222H2.9348742V17.69ZM26.2735913 4.0333333l.0114609 2.1694445h4.0126191V8.25h-4.001719L26.77 22h-3.535416L23.78 8.25h-2.4238344V6.2027778h2.55923l.0751088-2.1694445C24.0706908 1.6805556 25.6007488 0 27.697782 0 28.6896221 0 29.677687.3666667 30.5 1.0083333l-.9627285 1.6805556c-.3479788-.3666667-.9515992-.6416667-1.627768-.6416667-.8819593 0-1.6420082.825-1.6359122 1.9861111Z"/></svg>`,
  biggerCursor: `<svg xmlns="http://www.w3.org/2000/svg" version="1.2" viewBox="0 0 27 27"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15.9983464 11.5517813 9.5269972 9.52699721-4.4465655 4.44656549-9.5269972-9.52699717-4.05145413 9.06403815L1 1.0000004l24.0623846 6.5003268z"/></svg>`,
  lineHeight: `<svg xmlns="http://www.w3.org/2000/svg" version="1.2" viewBox="0 0 47 25"><g fill="none" fill-rule="evenodd"><path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M3.99999962 2.71042226V22.7104223"/><path fill="currentColor" d="m.16814235 20.5270412 3.44487862 4.2104072c.17486379.2137224.48987514.2452235.70359754.0703597a.4999988.4999988 0 0 0 .07035976-.0703597l3.44487862-4.2104072c.17486378-.2137225.14336265-.5287338-.07035976-.7035976-.08933106-.073089-.20119771-.1130213-.31661889-.1130213H.555121c-.27614238 0-.5.2238576-.5.5 0 .1154211.0399323.2272878.11302135.3166189Zm0-161332381L3.61302097.18339592c.17486379-.21372241.48987514-.24522355.70359754-.07035976a.49999975.49999975 0 0 1 .07035976.07035976l3.44487862 4.2104072c.17486378.2137224.14336265.52873375-.07035976.70359754-.08933106.07308905-.20119771.11302135-.31661889.11302135H.555121c-.27614237 0-.5-.22385762-.5-.5 0-.11542118.0399323-.22728783.11302135-.3166189Z"/><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.4999996 1.71042226h30m-30 7h30m-30 7.00000004h30m-30 7h24"/></g></svg>`,
  textAlign: `<svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><path d="M10 16h44v4H10zm0 12h44v4H10zm0 12h44v4H10zm0 12h44v4H10z"/></svg>`,
  screenReader: `<svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><path d="M16 24 L24 24 L32 16 L32 48 L24 40 L16 40 Z" fill="#333" stroke="#555" stroke-width="2"/><path d="M36 20 C42 24, 42 40, 36 44" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round"/><path d="M36 12 C48 24, 48 40, 36 52" fill="none" stroke="#555" stroke-width="2" stroke-linecap="round"/><rect x="28" y="48" width="8" height="8" fill="#ccc"/></svg>`,
  resetAll: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 17" width="100%" height="100%"><g fill="none" fill-rule="evenodd" stroke="currentColor" stroke-linecap="round" stroke-width="1.84"><path d="M16.20106 8c0 .9667-.189683 1.8872-.5324673 2.7251-.3427843.8372-.8386698 1.5911-1.4517524 2.2246-.6130825.6335-1.3426846 1.1459-2.152902 1.5001-.8108948.3542-1.70172746.5502-2.6372711.5502-.93554365 0-1.8263763-.196-2.63727112-.5502-.81021738-.3542-1.53981948-.8666-2.15290203-1.5001M2.6522744 8c0-.9667.189683-1.8872.53246728-2.7251.34278427-.8372.83866982-1.5911 1.45175237-2.2246.61308255-.6335 1.34268465-1.1459 2.15290203-1.5001C7.6002909 1.196 8.49112355 1 9.4266672 1c.93554364 0 1.8263763.196 2.6372711.5502.8102174.3542 1.5398195.8666 2.152902 1.5001"></path><path stroke-linejoin="round" d="m4.92576062 6.96092-2.48958935 1.484L1 5.87242m13.0125924 2.93832 2.3886509-1.652L18 9.62694"></path></g></svg>`,
  voiceControl: `<svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><path d="M32 44a12 12 0 0012-12V20a12 12 0 10-24 0v12a12 12 0 0012 12z" fill="#333"/><path d="M20 32h24v4H20z" fill="#555"/><path d="M32 48v8" stroke="#555" stroke-width="4" stroke-linecap="round"/></svg>`,
  fontSelection: `<svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><text x="32" y="40" font-family="serif" font-size="24" text-anchor="middle" fill="#333">Aa</text><path d="M8 48h48v2H8z"/></svg>`,
  colorFilter: `<svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><circle cx="32" cy="32" r="24" fill="none" stroke="#333" stroke-width="2"/><path d="M32 8a24 24 0 000 48V8z" fill="#f00" opacity="0.3"/><path d="M32 8a24 24 0 000 48" fill="none" stroke="#333" stroke-width="2" stroke-dasharray="4,2"/></svg>`,
  saturation: `<svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><circle cx="20" cy="32" r="12" fill="#ff0000" opacity="0.7"/><circle cx="32" cy="32" r="12" fill="#00ff00" opacity="0.7"/><circle cx="44" cy="32" r="12" fill="#0000ff" opacity="0.7"/></svg>`,
  reducedMotion: `<svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect x="16" y="24" width="8" height="16" fill="#333"/><rect x="28" y="24" width="8" height="16" fill="#333"/><rect x="40" y="24" width="8" height="16" fill="#333"/></svg>`,
};

// ===========================================
// SHADOW DOM SETUP
// ===========================================

let shadowRoot = null;

// Inject styles into the page (NOT the widget)
function injectPageStyles() {
  const styleSheet = document.createElement('style');
  styleSheet.innerText = pageStyles;
  styleSheet.id = 'snn-accessibility-page-styles';
  document.head.appendChild(styleSheet);

  // Add SVG color blindness filters to main document
  const svgFilters = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
  svgFilters.style.position = 'absolute';
  svgFilters.style.width = '0';
  svgFilters.setAttribute('class', 'snn-accessibility-filters');
  svgFilters.style.height = '0';
  svgFilters.innerHTML = `
    <defs>
      <filter id="protanopia-filter">
        <feColorMatrix type="matrix" values="0.567,0.433,0,0,0 0.558,0.442,0,0,0 0,0.242,0.758,0,0 0,0,0,1,0"/>
      </filter>
      <filter id="deuteranopia-filter">
        <feColorMatrix type="matrix" values="0.625,0.375,0,0,0 0.7,0.3,0,0,0 0,0.3,0.7,0,0 0,0,0,1,0"/>
      </filter>
      <filter id="tritanopia-filter">
        <feColorMatrix type="matrix" values="0.95,0.05,0,0,0 0,0.433,0.567,0,0 0,0.475,0.525,0,0 0,0,0,1,0"/>
      </filter>
    </defs>
  `;
  document.body.appendChild(svgFilters);
}

// Create shadow DOM container
function createShadowContainer() {
  const container = document.createElement('div');
  container.id = 'snn-accessibility-widget-container';
  document.body.appendChild(container);

  // Create shadow root
  shadowRoot = container.attachShadow({ mode: 'open' });

  // Add widget styles to shadow DOM
  const styleElement = document.createElement('style');
  styleElement.textContent = widgetStyles;
  shadowRoot.appendChild(styleElement);

  return shadowRoot;
}

// ===========================================
// CORE UTILITY FUNCTIONS
// ===========================================

// Cache for DOM elements to improve performance
const domCache = {
  get body() {
    return document.body;
  },
  get documentElement() {
    return document.documentElement;
  },
  images: null,
  lastImageUpdate: 0,
  getImages: function () {
    const now = Date.now();
    if (!this.images || now - this.lastImageUpdate > 5000) {
      this.images = document.querySelectorAll('img');
      this.lastImageUpdate = now;
    }
    return this.images;
  }
};

// Apply saved settings from localStorage (optimized)
function applySettings() {
  // Check if body element exists
  if (!domCache.body || !domCache.documentElement) {
    console.warn('Body or document element not ready yet');
    return;
  }

  const settings = [
    { key: 'biggerCursor', className: 'snn-bigger-cursor' },
    { key: 'biggerText', className: 'snn-bigger-text' },
    { key: 'highContrast', className: 'snn-high-contrast' },
    { key: 'dyslexiaFont', className: 'snn-dyslexia-font' },
    { key: 'textAlign', className: 'snn-text-align' },
    { key: 'pauseAnimations', className: 'snn-pause-animations' },
  ];

  // Batch DOM operations for better performance
  const bodyClassesToAdd = [];
  const bodyClassesToRemove = [];
  const docClassesToAdd = [];
  const docClassesToRemove = [];

  settings.forEach(({ key, className, target = domCache.body }) => {
    const isActive = localStorage.getItem(key) === 'true';
    if (className) {
      if (target === domCache.documentElement) {
        if (isActive) {
          docClassesToAdd.push(className);
        } else {
          docClassesToRemove.push(className);
        }
      } else {
        if (isActive) {
          bodyClassesToAdd.push(className);
        } else {
          bodyClassesToRemove.push(className);
        }
      }
    }
  });

  // Apply all class changes at once - ONLY remove classes that start with 'snn-'
  if (bodyClassesToAdd.length > 0) {
    domCache.body.classList.add(...bodyClassesToAdd);
  }
  if (bodyClassesToRemove.length > 0) {
    // Only remove our own classes, never remove classes that don't start with 'snn-'
    bodyClassesToRemove.forEach(className => {
      if (className.startsWith('snn-')) {
        domCache.body.classList.remove(className);
      }
    });
  }
  if (docClassesToAdd.length > 0) {
    domCache.documentElement.classList.add(...docClassesToAdd);
  }
  if (docClassesToRemove.length > 0) {
    // Only remove our own classes, never remove classes that don't start with 'snn-'
    docClassesToRemove.forEach(className => {
      if (className.startsWith('snn-')) {
        domCache.documentElement.classList.remove(className);
      }
    });
  }

  // Handle font selection - only remove widget's own font classes
  const fontClasses = ['snn-font-arial', 'snn-font-times', 'snn-font-verdana'];
  fontClasses.forEach(className => {
    if (domCache.body.classList.contains(className)) {
      domCache.body.classList.remove(className);
    }
  });
  const selectedFont = localStorage.getItem('fontSelection');
  if (selectedFont) {
    domCache.body.classList.add(`snn-font-${selectedFont}`);
  }

  // Handle color filters - only remove widget's own filter classes
  const filterClasses = ['snn-filter-protanopia', 'snn-filter-deuteranopia', 'snn-filter-tritanopia', 'snn-filter-grayscale'];
  filterClasses.forEach(className => {
    if (domCache.documentElement.classList.contains(className)) {
      domCache.documentElement.classList.remove(className);
    }
  });
  const selectedFilter = localStorage.getItem('colorFilter');
  if (selectedFilter) {
    domCache.documentElement.classList.add(`snn-filter-${selectedFilter}`);
  }

  // Handle saturation filters - only remove widget's own saturation classes
  const saturationClasses = ['snn-saturation-low', 'snn-saturation-high', 'snn-saturation-none'];
  saturationClasses.forEach(className => {
    if (domCache.documentElement.classList.contains(className)) {
      domCache.documentElement.classList.remove(className);
    }
  });
  const selectedSaturation = localStorage.getItem('saturation');
  if (selectedSaturation) {
    domCache.documentElement.classList.add(`snn-saturation-${selectedSaturation}`);
  }

  // Handle text alignment - only remove widget's own alignment classes
  const alignClasses = ['snn-text-align-left', 'snn-text-align-center', 'snn-text-align-right'];
  alignClasses.forEach(className => {
    if (domCache.body.classList.contains(className)) {
      domCache.body.classList.remove(className);
    }
  });
  const selectedAlign = localStorage.getItem('textAlign');
  if (selectedAlign) {
    domCache.body.classList.add(`snn-text-align-${selectedAlign}`);
  }

  // Handle bigger text - only remove widget's own text size classes
  const textClasses = ['snn-bigger-text-medium', 'snn-bigger-text-large', 'snn-bigger-text-xlarge'];
  textClasses.forEach(className => {
    if (domCache.body.classList.contains(className)) {
      domCache.body.classList.remove(className);
    }
  });
  const selectedTextSize = localStorage.getItem('biggerText');
  if (selectedTextSize) {
    domCache.body.classList.add(`snn-bigger-text-${selectedTextSize}`);
  }

  // Handle high contrast - only remove widget's own contrast classes
  const contrastClasses = ['snn-high-contrast-medium', 'snn-high-contrast-high', 'snn-high-contrast-ultra'];
  contrastClasses.forEach(className => {
    if (domCache.body.classList.contains(className)) {
      domCache.body.classList.remove(className);
    }
  });
  const selectedContrast = localStorage.getItem('highContrast');
  if (selectedContrast) {
    domCache.body.classList.add(`snn-high-contrast-${selectedContrast}`);
  }

  // Handle Text Spacing (3 Levels) - only remove widget's own spacing classes
  const spacingClasses = ['snn-text-spacing-light', 'snn-text-spacing-medium', 'snn-text-spacing-heavy'];
  spacingClasses.forEach(className => {
    if (domCache.body.classList.contains(className)) {
      domCache.body.classList.remove(className);
    }
  });
  const selectedSpacing = localStorage.getItem('textSpacing');
  if (selectedSpacing) {
    domCache.body.classList.add(`snn-text-spacing-${selectedSpacing}`);
  }

  // Handle Line Height (3 Levels) - only remove widget's own line height classes
  const lineHeightClasses = ['snn-line-height-2em', 'snn-line-height-3em', 'snn-line-height-4em'];
  lineHeightClasses.forEach(className => {
    if (domCache.body.classList.contains(className)) {
      domCache.body.classList.remove(className);
    }
  });
  const selectedLineHeight = localStorage.getItem('lineHeight');
  if (selectedLineHeight) {
    domCache.body.classList.add(`snn-line-height-${selectedLineHeight}`);
  }

  // Handle images with cached query
  const hideImages = localStorage.getItem('hideImages') === 'true';
  const displayStyle = hideImages ? 'none' : '';
  domCache.getImages().forEach((img) => {
    img.style.display = displayStyle;
  });

  if (screenReader.active && screenReader.isSupported) {
    document.addEventListener('focusin', screenReader.handleFocus);
  }

  if (voiceControl.isActive && voiceControl.isSupported) {
    voiceControl.startListening();
  }
}

// ===========================================
// UI COMPONENTS
// ===========================================

// Create the accessibility button
function createAccessibilityButton() {
  const buttonContainer = document.createElement('div');
  buttonContainer.id = 'snn-accessibility-fixed-button';

  const button = document.createElement('button');
  button.id = 'snn-accessibility-button';
  button.innerHTML = icons.buttonsvg;
  button.setAttribute('aria-label', getTranslation('accessibilityMenu'));

  button.addEventListener('click', function () {
    toggleMenu();
  });

  button.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      toggleMenu();
    }
  });

  buttonContainer.appendChild(button);
  shadowRoot.appendChild(buttonContainer);
}

// Reset all accessibility settings
function resetAccessibilitySettings() {
  const keys = [
    'biggerCursor',
    'biggerText',
    'dyslexiaFont',
    'hideImages',
    'lineHeight',
    'pauseAnimations',
    'screenReader',
    'textAlign',
    'textSpacing',
    'highContrast',
    'voiceControl',
    'fontSelection',
    'colorFilter',
    'saturation',
  ];
  keys.forEach((key) => localStorage.removeItem(key));

  // Remove only widget's own CSS classes - never touch existing body/document classes
  // Check if body and documentElement exist first
  if (!document.body || !document.documentElement) {
    console.warn('Body or document element not ready during reset');
    return;
  }

  // Remove body classes only if they exist and start with 'snn-'
  const cssClasses = [
    'snn-bigger-cursor',
    'snn-bigger-text',
    'snn-dyslexia-font',
    'snn-pause-animations',
    'snn-text-align',
    'snn-font-arial',
    'snn-font-times',
    'snn-font-verdana',
    'snn-high-contrast-medium',
    'snn-high-contrast-high',
    'snn-high-contrast-ultra',
    'snn-bigger-text-medium',
    'snn-bigger-text-large',
    'snn-bigger-text-xlarge',
    'snn-text-spacing-light',
    'snn-text-spacing-medium',
    'snn-text-spacing-heavy',
    'snn-line-height-2em',
    'snn-line-height-3em',
    'snn-line-height-4em',
    'snn-text-align-left',
    'snn-text-align-center',
    'snn-text-align-right'
  ];
  cssClasses.forEach(cls => {
    if (document.body.classList.contains(cls)) {
      document.body.classList.remove(cls);
    }
  });

  // Remove document element classes only if they exist and start with 'snn-'
  const documentClasses = [
    'snn-filter-protanopia',
    'snn-filter-deuteranopia',
    'snn-filter-tritanopia',
    'snn-filter-grayscale',
    'snn-saturation-low',
    'snn-saturation-high',
    'snn-saturation-none'
  ];
  documentClasses.forEach(cls => {
    if (document.documentElement.classList.contains(cls)) {
      document.documentElement.classList.remove(cls);
    }
  });

  domCache.getImages().forEach((img) => (img.style.display = ''));

  if (screenReader.active) {
    screenReader.toggle(false);
  }

  if (voiceControl.isActive) {
    voiceControl.toggle(false);
  }

  applySettings();

  const buttons = shadowRoot.querySelectorAll('#snn-accessibility-menu .snn-accessibility-option');
  buttons.forEach((button) => {
    button.classList.remove('active');
    button.setAttribute('aria-pressed', 'false');
    
    // Reset step indicators
    const steps = button.querySelectorAll('.snn-option-step');
    steps.forEach(step => step.classList.remove('active'));
  });
}

// Create toggle buttons for accessibility options
function createToggleButton(
  buttonText,
  localStorageKey,
  className,
  targetElement = document.body,
  customToggleFunction = null,
  iconSVG = '',
  requiresFeature = null,
  optionId = null
) {
  const button = document.createElement('button');
  button.innerHTML = `
    <span class="snn-icon">${iconSVG}</span>
    <span class="snn-button-text">${buttonText}</span>
  `;
  button.setAttribute('data-key', localStorageKey);
  button.setAttribute('aria-label', buttonText);
  button.classList.add('snn-accessibility-option');
  if (optionId) {
    button.setAttribute('data-accessibility-option-id', optionId);
  }

  // Check if feature is supported
  if (requiresFeature && !requiresFeature.isSupported) {
    button.disabled = true;
    button.setAttribute('title', `${buttonText} ${getTranslation('notSupportedBrowser')}`);
    button.style.opacity = '0.5';
    return button;
  }

  const isActive = localStorage.getItem(localStorageKey) === 'true';
  button.setAttribute('aria-pressed', isActive);
  button.setAttribute('role', 'switch');
  if (isActive) {
    button.classList.add('active');
  }

  button.addEventListener('click', function () {
    handleToggle();
  });

  button.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      handleToggle();
    }
  });

  function handleToggle() {
    const newIsActive = localStorage.getItem(localStorageKey) !== 'true';

    // If there's a custom toggle function, call it and check if it succeeded
    if (customToggleFunction) {
      const success = customToggleFunction(newIsActive);
      if (success === false) {
        // Feature not supported or failed
        return;
      }
    }

    localStorage.setItem(localStorageKey, newIsActive);
    button.setAttribute('aria-pressed', newIsActive);

    if (newIsActive) {
      button.classList.add('active');
      if (className) {
        targetElement.classList.add(className);
      }
    } else {
      button.classList.remove('active');
      if (className) {
        targetElement.classList.remove(className);
      }
    }
  }

  return button;
}

// Create special action buttons (for cycling through options)
function createActionButton(buttonText, actionFunction, iconSVG, optionsConfig = null, optionId = null) {
  const button = document.createElement('button');
  
  let buttonHTML = `
    <span class="snn-icon">${iconSVG}</span>
    <span class="snn-button-text">${buttonText}</span>
  `;
  
  // Add option steps if configured
  if (optionsConfig) {
    buttonHTML += '<div class="snn-option-steps">';
    for (let i = 0; i < optionsConfig.count; i++) {
      buttonHTML += '<div class="snn-option-step"></div>';
    }
    buttonHTML += '</div>';
  }
  
  button.innerHTML = buttonHTML;
  button.setAttribute('aria-label', buttonText);
  button.classList.add('snn-accessibility-option');
  button.setAttribute('data-options-config', optionsConfig ? JSON.stringify(optionsConfig) : '');
  if (optionId) {
    button.setAttribute('data-accessibility-option-id', optionId);
    button.setAttribute('data-key', optionId); // Add data-key for voice commands
  }

  // Update initial status
  updateActionButtonStatus(button, optionId, optionsConfig);

  button.addEventListener('click', function () {
    const result = actionFunction();
    if (result) {
      updateActionButtonStatus(button, optionId, optionsConfig);
    }
  });

  button.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      const result = actionFunction();
      if (result) {
        updateActionButtonStatus(button, optionId, optionsConfig);
      }
    }
  });

  return button;
}

// Update action button status on page load
function updateActionButtonStatus(button, optionId, optionsConfig) {
  if (!optionsConfig) return;
  
  const steps = button.querySelectorAll('.snn-option-step');
  let currentIndex = -1;
  
  if (optionId === 'fontSelection') {
    const currentFont = localStorage.getItem('fontSelection');
    const fonts = ['arial', 'times', 'verdana'];
    currentIndex = currentFont ? fonts.indexOf(currentFont) : -1;
  } else if (optionId === 'colorFilter') {
    const currentFilter = localStorage.getItem('colorFilter');
    const filters = ['protanopia', 'deuteranopia', 'tritanopia', 'grayscale'];
    currentIndex = currentFilter ? filters.indexOf(currentFilter) : -1;
  } else if (optionId === 'textAlign') {
    const currentAlign = localStorage.getItem('textAlign');
    const alignments = ['left', 'center', 'right'];
    currentIndex = currentAlign ? alignments.indexOf(currentAlign) : -1;
  } else if (optionId === 'biggerText') {
    const currentSize = localStorage.getItem('biggerText');
    const sizes = ['medium', 'large', 'xlarge'];
    currentIndex = currentSize ? sizes.indexOf(currentSize) : -1;
  } else if (optionId === 'highContrast') {
    const currentContrast = localStorage.getItem('highContrast');
    const contrasts = ['medium', 'high', 'ultra'];
    currentIndex = currentContrast ? contrasts.indexOf(currentContrast) : -1;
  } else if (optionId === 'textSpacing') {
    const currentSpacing = localStorage.getItem('textSpacing');
    const spacings = ['light', 'medium', 'heavy'];
    currentIndex = currentSpacing ? spacings.indexOf(currentSpacing) : -1;
  } else if (optionId === 'lineHeight') {
    const currentLineHeight = localStorage.getItem('lineHeight');
    const heights = ['2em', '3em', '4em'];
    currentIndex = currentLineHeight ? heights.indexOf(currentLineHeight) : -1;
  } else if (optionId === 'saturation') {
    const currentSaturation = localStorage.getItem('saturation');
    const saturations = ['low', 'high', 'none'];
    currentIndex = currentSaturation ? saturations.indexOf(currentSaturation) : -1;
  }
  
  // Update step indicators - show all previous steps as active
  steps.forEach((step, index) => {
    if (index <= currentIndex) {
      step.classList.add('active');
    } else {
      step.classList.remove('active');
    }
  });

  // Toggle active class on button itself if any option is selected
  if (currentIndex !== -1) {
    button.classList.add('active');
    button.setAttribute('aria-pressed', 'true');
  } else {
    button.classList.remove('active');
    button.setAttribute('aria-pressed', 'false');
  }
}

// ===========================================
// FEATURE TOGGLE FUNCTIONS
// ===========================================

// Function to hide or show images (optimized)
function toggleHideImages(isActive) {
  const displayStyle = isActive ? 'none' : '';
  domCache.getImages().forEach((img) => {
    img.style.display = displayStyle;
  });
}

// Font selection handler (optimized)
function handleFontSelection() {
  const fonts = ['arial', 'times', 'verdana'];
  const currentFont = localStorage.getItem('fontSelection') || 'default';
  const currentIndex = fonts.indexOf(currentFont);
  const nextIndex = (currentIndex + 1) % (fonts.length + 1); // +1 for default

  // Remove only widget's own font classes
  const fontClasses = ['snn-font-arial', 'snn-font-times', 'snn-font-verdana'];
  fontClasses.forEach(className => {
    if (domCache.body.classList.contains(className)) {
      domCache.body.classList.remove(className);
    }
  });

  if (nextIndex === fonts.length) {
    // Default font
    localStorage.removeItem('fontSelection');
    return getTranslation('defaultFont');
  } else {
    const selectedFont = fonts[nextIndex];
    localStorage.setItem('fontSelection', selectedFont);
    domCache.body.classList.add(`snn-font-${selectedFont}`);
    return selectedFont.charAt(0).toUpperCase() + selectedFont.slice(1);
  }
}

// Saturation handler with 3 states (low, high, none/grayscale)
function handleSaturation() {
  const saturations = ['low', 'high', 'none'];
  const currentSaturation = localStorage.getItem('saturation') || 'default';
  const currentIndex = saturations.indexOf(currentSaturation);
  const nextIndex = (currentIndex + 1) % (saturations.length + 1); // +1 for default

  // Remove only widget's own saturation classes
  const saturationClasses = ['snn-saturation-low', 'snn-saturation-high', 'snn-saturation-none'];
  saturationClasses.forEach(className => {
    if (domCache.documentElement.classList.contains(className)) {
      domCache.documentElement.classList.remove(className);
    }
  });

  if (nextIndex === saturations.length) {
    // Default saturation
    localStorage.removeItem('saturation');
    return 'Default';
  } else {
    const selectedSaturation = saturations[nextIndex];
    localStorage.setItem('saturation', selectedSaturation);
    domCache.documentElement.classList.add(`snn-saturation-${selectedSaturation}`);
    if (selectedSaturation === 'none') {
      return 'No Saturation';
    }
    return selectedSaturation.charAt(0).toUpperCase() + selectedSaturation.slice(1) + ' Saturation';
  }
}

// Color filter handler (optimized)
function handleColorFilter() {
  const filters = ['protanopia', 'deuteranopia', 'tritanopia', 'grayscale'];
  const currentFilter = localStorage.getItem('colorFilter') || 'none';
  const currentIndex = filters.indexOf(currentFilter);
  const nextIndex = (currentIndex + 1) % (filters.length + 1); // +1 for none

  // Remove only widget's own filter classes
  const filterClasses = ['snn-filter-protanopia', 'snn-filter-deuteranopia', 'snn-filter-tritanopia', 'snn-filter-grayscale'];
  filterClasses.forEach(className => {
    if (domCache.documentElement.classList.contains(className)) {
      domCache.documentElement.classList.remove(className);
    }
  });

  if (nextIndex === filters.length) {
    // No filter
    localStorage.removeItem('colorFilter');
    return getTranslation('noFilter');
  } else {
    const selectedFilter = filters[nextIndex];
    localStorage.setItem('colorFilter', selectedFilter);
    domCache.documentElement.classList.add(`snn-filter-${selectedFilter}`);
    return selectedFilter.charAt(0).toUpperCase() + selectedFilter.slice(1);
  }
}

// Text align handler with 3 states
function handleTextAlign() {
  const alignments = ['left', 'center', 'right'];
  const currentAlign = localStorage.getItem('textAlign') || 'none';
  const currentIndex = alignments.indexOf(currentAlign);
  const nextIndex = (currentIndex + 1) % (alignments.length + 1); // +1 for none

  // Remove only widget's own alignment classes
  const alignClasses = ['snn-text-align-left', 'snn-text-align-center', 'snn-text-align-right'];
  alignClasses.forEach(className => {
    if (domCache.body.classList.contains(className)) {
      domCache.body.classList.remove(className);
    }
  });

  if (nextIndex === alignments.length) {
    // Default alignment
    localStorage.removeItem('textAlign');
    return getTranslation('default');
  } else {
    const selectedAlign = alignments[nextIndex];
    localStorage.setItem('textAlign', selectedAlign);
    domCache.body.classList.add(`snn-text-align-${selectedAlign}`);
    return selectedAlign.charAt(0).toUpperCase() + selectedAlign.slice(1);
  }
}

// Bigger text handler with 3 states
function handleBiggerText() {
  const textSizes = ['medium', 'large', 'xlarge'];
  const currentSize = localStorage.getItem('biggerText') || 'none';
  const currentIndex = textSizes.indexOf(currentSize);
  const nextIndex = (currentIndex + 1) % (textSizes.length + 1); // +1 for none

  // Remove only widget's own text size classes
  const textClasses = ['snn-bigger-text-medium', 'snn-bigger-text-large', 'snn-bigger-text-xlarge'];
  textClasses.forEach(className => {
    if (domCache.body.classList.contains(className)) {
      domCache.body.classList.remove(className);
    }
  });

  if (nextIndex === textSizes.length) {
    // Default text size
    localStorage.removeItem('biggerText');
    return getTranslation('default');
  } else {
    const selectedSize = textSizes[nextIndex];
    localStorage.setItem('biggerText', selectedSize);
    domCache.body.classList.add(`snn-bigger-text-${selectedSize}`);
    return selectedSize === 'xlarge' ? 'X-Large' : selectedSize.charAt(0).toUpperCase() + selectedSize.slice(1);
  }
}

// High contrast handler with 3 states
function handleHighContrast() {
  const contrastLevels = ['medium', 'high', 'ultra'];
  const currentContrast = localStorage.getItem('highContrast') || 'none';
  const currentIndex = contrastLevels.indexOf(currentContrast);
  const nextIndex = (currentIndex + 1) % (contrastLevels.length + 1); // +1 for none

  // Remove only widget's own contrast classes
  const contrastClasses = ['snn-high-contrast-medium', 'snn-high-contrast-high', 'snn-high-contrast-ultra'];
  contrastClasses.forEach(className => {
    if (domCache.body.classList.contains(className)) {
      domCache.body.classList.remove(className);
    }
  });

  if (nextIndex === contrastLevels.length) {
    // Default contrast
    localStorage.removeItem('highContrast');
    return getTranslation('default');
  } else {
    const selectedContrast = contrastLevels[nextIndex];
    localStorage.setItem('highContrast', selectedContrast);
    domCache.body.classList.add(`snn-high-contrast-${selectedContrast}`);
    return selectedContrast.charAt(0).toUpperCase() + selectedContrast.slice(1);
  }
}

// Text Spacing Handler with 3 states (1em, 2em, 4em equivalents)
function handleTextSpacing() {
  const spacings = ['light', 'medium', 'heavy']; // Maps to 1, 2, 4 approx
  const currentSpacing = localStorage.getItem('textSpacing') || 'none';
  const currentIndex = spacings.indexOf(currentSpacing);
  const nextIndex = (currentIndex + 1) % (spacings.length + 1); // +1 for none

  // Remove only widget's own spacing classes
  const spacingClasses = ['snn-text-spacing-light', 'snn-text-spacing-medium', 'snn-text-spacing-heavy'];
  spacingClasses.forEach(className => {
    if (domCache.body.classList.contains(className)) {
      domCache.body.classList.remove(className);
    }
  });

  if (nextIndex === spacings.length) {
    // Default
    localStorage.removeItem('textSpacing');
    return getTranslation('default');
  } else {
    const selectedSpacing = spacings[nextIndex];
    localStorage.setItem('textSpacing', selectedSpacing);
    domCache.body.classList.add(`snn-text-spacing-${selectedSpacing}`);
    return selectedSpacing.charAt(0).toUpperCase() + selectedSpacing.slice(1);
  }
}

// Line Height Handler with 3 states (2em, 3em, 4em)
function handleLineHeight() {
  const heights = ['2em', '3em', '4em'];
  const currentHeight = localStorage.getItem('lineHeight') || 'none';
  const currentIndex = heights.indexOf(currentHeight);
  const nextIndex = (currentIndex + 1) % (heights.length + 1); // +1 for none

  // Remove only widget's own line height classes
  const heightClasses = ['snn-line-height-2em', 'snn-line-height-3em', 'snn-line-height-4em'];
  heightClasses.forEach(className => {
    if (domCache.body.classList.contains(className)) {
      domCache.body.classList.remove(className);
    }
  });

  if (nextIndex === heights.length) {
    // Default
    localStorage.removeItem('lineHeight');
    return getTranslation('default');
  } else{
    const selectedHeight = heights[nextIndex];
    localStorage.setItem('lineHeight', selectedHeight);
    domCache.body.classList.add(`snn-line-height-${selectedHeight}`);
    return selectedHeight;
  }
}

// ===========================================
// ACCESSIBILITY FEATURES
// ===========================================

// Screen reader functionality
const screenReader = {
  active: localStorage.getItem('screenReader') === 'true',
  isSupported: 'speechSynthesis' in window,
  handleFocus: function (event) {
    if (screenReader.active && screenReader.isSupported) {
      try {
        const content = event.target.innerText || event.target.alt || event.target.title || '';
        if (content.trim() !== '') {
          window.speechSynthesis.cancel();
          const speech = new SpeechSynthesisUtterance(content);
          
          // Set language based on current interface language
          let speechLang = 'en-US'; // default
          switch(currentLanguage) {
            case 'de': speechLang = 'de-DE'; break;
            case 'es': speechLang = 'es-ES'; break;
            case 'it': speechLang = 'it-IT'; break;
            case 'fr': speechLang = 'fr-FR'; break;
            case 'ru': speechLang = 'ru-RU'; break;
            case 'tr': speechLang = 'tr-TR'; break;
            case 'ar': speechLang = 'ar-SA'; break;
            case 'hi': speechLang = 'hi-IN'; break;
            case 'zh-cn': speechLang = 'zh-CN'; break;
            case 'jp': speechLang = 'ja-JP'; break;
            default: speechLang = 'en-US';
          }
          speech.lang = speechLang;
          
          speech.onerror = function (event) {
            console.warn('Speech synthesis error:', event.error);
          };
          window.speechSynthesis.speak(speech);
        }
      } catch (error) {
        console.warn('Screen reader error:', error);
      }
    }
  },
  toggle: function (isActive) {
    if (!screenReader.isSupported) {
      console.warn(`Speech synthesis ${getTranslation('notSupportedBrowser')}`);
      return false;
    }

    screenReader.active = isActive;
    localStorage.setItem('screenReader', isActive);

    try {
      // Set language based on current interface language
      let speechLang = 'en-US'; // default
      switch(currentLanguage) {
        case 'de': speechLang = 'de-DE'; break;
        case 'es': speechLang = 'es-ES'; break;
        case 'it': speechLang = 'it-IT'; break;
        case 'fr': speechLang = 'fr-FR'; break;
        case 'ru': speechLang = 'ru-RU'; break;
        case 'tr': speechLang = 'tr-TR'; break;
        case 'ar': speechLang = 'ar-SA'; break;
        case 'hi': speechLang = 'hi-IN'; break;
        case 'zh-cn': speechLang = 'zh-CN'; break;
        case 'jp': speechLang = 'ja-JP'; break;
        default: speechLang = 'en-US';
      }

      if (isActive) {
        document.addEventListener('focusin', screenReader.handleFocus);
        const feedbackSpeech = new SpeechSynthesisUtterance(getTranslation('screenReaderOn'));
        feedbackSpeech.lang = speechLang;
        feedbackSpeech.onerror = function (event) {
          console.warn('Speech synthesis feedback error:', event.error);
        };
        window.speechSynthesis.speak(feedbackSpeech);
      } else {
        document.removeEventListener('focusin', screenReader.handleFocus);
        window.speechSynthesis.cancel();
        const feedbackSpeech = new SpeechSynthesisUtterance(getTranslation('screenReaderOff'));
        feedbackSpeech.lang = speechLang;
        feedbackSpeech.onerror = function (event) {
          console.warn('Speech synthesis feedback error:', event.error);
        };
        window.speechSynthesis.speak(feedbackSpeech);
      }
    } catch (error) {
      console.warn('Screen reader toggle error:', error);
      return false;
    }

    return true;
  },
};

// Voice control functionality
const voiceControl = {
  isActive: localStorage.getItem('voiceControl') === 'true',
  recognition: null,
  isSupported: 'SpeechRecognition' in window || 'webkitSpeechRecognition' in window,
  retryCount: 0,
  maxRetries: 3,
  toggle: function (isActive) {
    if (!voiceControl.isSupported) {
      console.warn(`Speech Recognition API ${getTranslation('notSupportedBrowser')}`);
      return false;
    }

    voiceControl.isActive = isActive;
    localStorage.setItem('voiceControl', isActive);

    try {
      if (isActive) {
        voiceControl.startListening();
      } else {
        if (voiceControl.recognition) {
          voiceControl.recognition.stop();
          voiceControl.recognition = null;
        }
        voiceControl.retryCount = 0;
      }
    } catch (error) {
      console.warn('Voice control toggle error:', error);
      return false;
    }

    return true;
  },
  startListening: function () {
    if (!voiceControl.isSupported) {
      return;
    }

    try {
      const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
      voiceControl.recognition = new SpeechRecognition();
      voiceControl.recognition.interimResults = false;
      
      // Set language based on current interface language
      let recognitionLang = 'en-US'; // default
      switch(currentLanguage) {
        case 'de': recognitionLang = 'de-DE'; break;
        case 'es': recognitionLang = 'es-ES'; break;
        case 'it': recognitionLang = 'it-IT'; break;
        case 'fr': recognitionLang = 'fr-FR'; break;
        case 'ru': recognitionLang = 'ru-RU'; break;
        case 'tr': recognitionLang = 'tr-TR'; break;
        case 'ar': recognitionLang = 'ar-SA'; break;
        case 'hi': recognitionLang = 'hi-IN'; break;
        case 'zh-cn': recognitionLang = 'zh-CN'; break;
        case 'jp': recognitionLang = 'ja-JP'; break;
        default: recognitionLang = 'en-US';
      }
      voiceControl.recognition.lang = recognitionLang;
      voiceControl.recognition.continuous = false;

      voiceControl.recognition.onstart = function () {
        console.log(getTranslation('voiceControlActivated'));
        voiceControl.retryCount = 0;
      };

      voiceControl.recognition.onresult = function (event) {
        try {
          const command = event.results[0][0].transcript.toLowerCase();
          voiceControl.handleVoiceCommand(command);
        } catch (error) {
          console.warn('Voice command processing error:', error);
        }
      };

      voiceControl.recognition.onerror = function (event) {
        console.warn('Speech recognition error:', event.error);
        if (event.error === 'no-speech' && voiceControl.retryCount < voiceControl.maxRetries) {
          voiceControl.retryCount++;
          setTimeout(() => {
            if (voiceControl.isActive) {
              voiceControl.startListening();
            }
          }, 1000);
        }
      };

      voiceControl.recognition.onend = function () {
        if (voiceControl.isActive && voiceControl.retryCount < voiceControl.maxRetries) {
          setTimeout(() => {
            if (voiceControl.isActive) {
              voiceControl.startListening();
            }
          }, 100);
        }
      };

      voiceControl.recognition.start();
    } catch (error) {
      console.warn('Voice control initialization error:', error);
    }
  },
  handleVoiceCommand: function (command) {
    console.log(`Received command: ${command}`);

    try {
      // Normalize the command by removing extra spaces and making it lowercase
      const normalizedCommand = command.toLowerCase().trim().replace(/\s+/g, ' ');
      
      // Get voice commands for current language, fallback to English
      const languageCommands = WIDGET_CONFIG.voiceCommands[currentLanguage] || WIDGET_CONFIG.voiceCommands['en'];
      
      // Check for show menu commands
      if (languageCommands.showMenu.some(cmd => normalizedCommand.includes(cmd))) {
        if (!menuCache.button) menuCache.init();
        if (menuCache.button) {
          menuCache.button.click();
          console.log('Successfully opened menu');
        }
        return;
      }

      // Check for reset all commands
      if (languageCommands.resetAll.some(cmd => normalizedCommand.includes(cmd))) {
        resetAccessibilitySettings();
        console.log('Successfully reset all settings');
        return;
      }

      // Build dynamic command map based on configuration
      let localStorageKey = null;
      let matchedCommand = null;

      // Check each command group with better matching
      for (const [key, commands] of Object.entries(languageCommands)) {
        if (key === 'showMenu' || key === 'resetAll') continue; // Already handled above
        
        const isMatch = commands.some(cmd => {
          // Check for exact matches first
          if (normalizedCommand.includes(cmd.toLowerCase())) {
            matchedCommand = cmd;
            return true;
          }
          // Check for partial word matches (at least 3 characters)
          const cmdWords = cmd.toLowerCase().split(' ');
          const inputWords = normalizedCommand.split(' ');
          return cmdWords.some(cmdWord => 
            cmdWord.length >= 3 && inputWords.some(inputWord => 
              inputWord.includes(cmdWord) || cmdWord.includes(inputWord)
            )
          );
        });
        
        if (isMatch) {
          localStorageKey = key;
          break;
        }
      }

      if (localStorageKey) {
        // Use cached menu reference if available
        if (!menuCache.menu) menuCache.init();
        
        // Try to find button by data-key first (toggle buttons)
        let button = menuCache.menu?.querySelector(
          `.snn-accessibility-option[data-key='${localStorageKey}']`
        );
        
        // If not found, try to find by data-accessibility-option-id (action buttons)
        if (!button) {
          button = menuCache.menu?.querySelector(
            `.snn-accessibility-option[data-accessibility-option-id='${localStorageKey}']`
          );
        }
        
        if (button) {
          button.click();
          console.log(`Successfully executed command: ${command} (matched: ${matchedCommand || localStorageKey})`);
        } else {
          console.log('Button not found for command:', command, '(key:', localStorageKey, ')');
        }
      } else {
        console.log('Command not recognized:', command);
        // Provide helpful suggestions
        const availableCommands = Object.values(languageCommands).flat();
        const suggestions = availableCommands.filter(cmd => 
          cmd.toLowerCase().includes(normalizedCommand.split(' ')[0]) ||
          normalizedCommand.split(' ')[0].includes(cmd.toLowerCase().split(' ')[0])
        );
        if (suggestions.length > 0) {
          console.log('Did you mean one of these?', suggestions.slice(0, 3));
        }
      }
    } catch (error) {
      console.warn('Voice command handling error:', error);
    }
  },
};

// Create the accessibility menu
function createAccessibilityMenu() {
  const menu = document.createElement('div');
  menu.id = 'snn-accessibility-menu';
  menu.style.display = 'none';
  menu.setAttribute('role', 'dialog');
  menu.setAttribute('aria-labelledby', 'snn-accessibility-title');
  menu.setAttribute('aria-hidden', 'true');

  const header = document.createElement('div');
  header.classList.add('snn-header');

  const title = document.createElement('div');
  title.classList.add('snn-title');
  title.id = 'snn-accessibility-title';
  title.textContent = getTranslation('accessibilityTools');

  // Create reset button
  const resetButton = document.createElement('button');
  resetButton.classList.add('snn-reset-button');
  resetButton.innerHTML = `${icons.resetAll}<span class="snn-tooltip">${getTranslation('reset')}</span>`;
  resetButton.setAttribute('aria-label', getTranslation('resetAllSettings'));
  resetButton.addEventListener('click', resetAccessibilitySettings);

  // Create close button
  const closeButton = document.createElement('button');
  closeButton.className = 'snn-close';
  closeButton.innerHTML = `<span class="snn-tooltip">${getTranslation('close')}</span>`;
  closeButton.setAttribute('aria-label', getTranslation('closeAccessibilityMenu'));

  closeButton.addEventListener('click', function () {
    closeMenu();
  });

  closeButton.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      closeMenu();
    }
  });

  header.appendChild(title);
  header.appendChild(resetButton);
  header.appendChild(closeButton);
  menu.appendChild(header);

  // Create content wrapper
  const content = document.createElement('div');
  content.classList.add('snn-content');

  // Create language selector dropdown
  const languageSelector = document.createElement('select');
  languageSelector.classList.add('snn-language-selector');
  languageSelector.setAttribute('aria-label', getTranslation('selectLanguage'));
  
  const languages = [
    { code: 'en', name: 'English' },
    { code: 'de', name: 'Deutsch' },
    { code: 'es', name: 'Español' },
    { code: 'it', name: 'Italiano' },
    { code: 'fr', name: 'Français' },
    { code: 'ru', name: 'Русский' },
    { code: 'tr', name: 'Türkçe' },
    { code: 'ar', name: 'العربية' },
    { code: 'hi', name: 'हिन्दी' },
    { code: 'zh-cn', name: '简体中文' },
    { code: 'jp', name: '日本語' }
  ];
  
  languages.forEach(lang => {
    const option = document.createElement('option');
    option.value = lang.code;
    option.textContent = lang.name;
    if (lang.code === currentLanguage) {
      option.selected = true;
    }
    languageSelector.appendChild(option);
  });
  
  languageSelector.addEventListener('change', function(e) {
    const newLang = e.target.value;
    if (setLanguage(newLang)) {
      // Recreate the menu with new language
      updateMenuLanguage();
    }
  });
  
  content.appendChild(languageSelector);

  // Create grid wrapper for accessibility options
  const optionsGrid = document.createElement('div');
  optionsGrid.classList.add('snn-options-grid');

  // ===================================================================
  // UNIFIED BUTTON CONFIGURATION WITH EXPLICIT ORDERING
  // Add/remove/reorder buttons by changing the 'order' property
  // Lower order numbers appear first, higher numbers appear last
  // ===================================================================
  const allButtonConfigs = [
    // Order 1-4: Primary accessibility features
    {
      order: 1,
      type: 'action',
      text: getTranslation('textSize'),
      actionFunction: handleBiggerText,
      icon: icons.biggerText,
      enabled: WIDGET_CONFIG.enableBiggerText,
      optionsConfig: { count: 3 },
      optionId: 'biggerText'
    },
    {
      order: 2,
      type: 'action',
      text: getTranslation('highContrast'),
      actionFunction: handleHighContrast,
      icon: icons.highContrast,
      enabled: WIDGET_CONFIG.enableHighContrast,
      optionsConfig: { count: 3 },
      optionId: 'highContrast'
    },
    {
      order: 3,
      type: 'action',
      text: getTranslation('textAlign'),
      actionFunction: handleTextAlign,
      icon: icons.textAlign,
      enabled: WIDGET_CONFIG.enableTextAlign,
      optionsConfig: { count: 3 },
      optionId: 'textAlign'
    },
    {
      order: 4,
      type: 'action',
      text: getTranslation('colorFilter'),
      actionFunction: handleColorFilter,
      icon: icons.colorFilter,
      enabled: WIDGET_CONFIG.enableColorFilter,
      optionsConfig: { count: 4 },
      optionId: 'colorFilter'
    },
    
    // Order 5-11: Other visual/text features
    {
      order: 5,
      type: 'action', // Changed from toggle to action
      text: getTranslation('textSpacing'),
      actionFunction: handleTextSpacing,
      icon: icons.textSpacing,
      enabled: WIDGET_CONFIG.enableTextSpacing,
      optionsConfig: { count: 3 },
      optionId: 'textSpacing'
    },
    {
      order: 6,
      type: 'action', // Changed from toggle to action
      text: getTranslation('lineHeight'),
      actionFunction: handleLineHeight,
      icon: icons.lineHeight,
      enabled: WIDGET_CONFIG.enableLineHeight,
      optionsConfig: { count: 3 },
      optionId: 'lineHeight'
    },
    {
      order: 7,
      type: 'action',
      text: getTranslation('fontSelection'),
      actionFunction: handleFontSelection,
      icon: icons.fontSelection,
      enabled: WIDGET_CONFIG.enableFontSelection,
      optionsConfig: { count: 3 },
      optionId: 'fontSelection'
    },
    {
      order: 7.5,
      type: 'action',
      text: getTranslation('saturation'),
      actionFunction: handleSaturation,
      icon: icons.saturation,
      enabled: true,
      optionsConfig: { count: 3 },
      optionId: 'saturation'
    },
    {
      order: 8,
      type: 'toggle',
      text: getTranslation('dyslexiaFriendly'),
      key: 'dyslexiaFont',
      className: 'snn-dyslexia-font',
      icon: icons.dyslexiaFont,
      enabled: WIDGET_CONFIG.enableDyslexiaFont,
      optionId: 'dyslexiaFont'
    },
    {
      order: 9,
      type: 'toggle',
      text: getTranslation('biggerCursor'),
      key: 'biggerCursor',
      className: 'snn-bigger-cursor',
      icon: icons.biggerCursor,
      enabled: WIDGET_CONFIG.enableBiggerCursor,
      optionId: 'biggerCursor'
    },
    {
      order: 10,
      type: 'toggle',
      text: getTranslation('hideImages'),
      key: 'hideImages',
      icon: icons.hideImages,
      customToggleFunction: toggleHideImages,
      enabled: WIDGET_CONFIG.enableHideImages,
      optionId: 'hideImages'
    },
    
    // Order 11: Animation controls (Reduced Motion merged here)
    {
      order: 11,
      type: 'toggle',
      text: getTranslation('pauseAnimations'),
      key: 'pauseAnimations',
      className: 'snn-pause-animations',
      icon: icons.pauseAnimations,
      enabled: WIDGET_CONFIG.enablePauseAnimations,
      optionId: 'pauseAnimations'
    },
    
    // Order 98-99: Screen Reader and Voice Control (always last)
    {
      order: 98,
      type: 'toggle',
      text: getTranslation('screenReader'),
      key: 'screenReader',
      customToggleFunction: screenReader.toggle,
      icon: icons.screenReader,
      requiresFeature: screenReader,
      enabled: WIDGET_CONFIG.enableScreenReader,
      optionId: 'screenReader'
    },
    {
      order: 99,
      type: 'toggle',
      text: getTranslation('voiceCommand'),
      key: 'voiceControl',
      customToggleFunction: voiceControl.toggle,
      icon: icons.voiceControl,
      requiresFeature: voiceControl,
      enabled: WIDGET_CONFIG.enableVoiceControl,
      optionId: 'voiceControl'
    },
  ];

  // Sort buttons by order and add only enabled ones to the grid
  allButtonConfigs
    .filter(config => config.enabled)
    .sort((a, b) => a.order - b.order)
    .forEach((config) => {
      let button;
      
      if (config.type === 'action') {
        button = createActionButton(config.text, config.actionFunction, config.icon, config.optionsConfig, config.optionId);
      } else if (config.type === 'toggle') {
        button = createToggleButton(
          config.text,
          config.key,
          config.className,
          config.target || document.body,
          config.customToggleFunction,
          config.icon,
          config.requiresFeature,
          config.optionId
        );
      }
      
      if (button) {
        optionsGrid.appendChild(button);
      }
    });

  // Add grid to content
  content.appendChild(optionsGrid);

  // Add content to menu
  menu.appendChild(content);

  shadowRoot.appendChild(menu);
}

// Update menu language without recreating everything
function updateMenuLanguage() {
  const menu = shadowRoot.getElementById('snn-accessibility-menu');
  if (!menu) return;
  
  const wasOpen = menu.style.display === 'block';
  
  // Remove old menu
  menu.remove();
  
  // Clear cache
  menuCache.menu = null;
  menuCache.closeButton = null;
  keyboardCache.focusableElements = null;
  
  // Recreate menu
  createAccessibilityMenu();
  
  // Update button aria-label
  const mainButton = shadowRoot.getElementById('snn-accessibility-button');
  if (mainButton) {
    mainButton.setAttribute('aria-label', getTranslation('accessibilityMenu'));
  }
  
  // Reopen if it was open
  if (wasOpen) {
    menuCache.init();
    openMenu();
  }
}

// ===========================================
// MENU MANAGEMENT
// ===========================================

// Cache for menu elements
const menuCache = {
  menu: null,
  button: null,
  closeButton: null,
  init: function () {
    this.menu = shadowRoot.getElementById('snn-accessibility-menu');
    this.button = shadowRoot.getElementById('snn-accessibility-button');
    this.closeButton = this.menu?.querySelector('.snn-close');
  }
};

// Menu control functions (optimized)
function toggleMenu() {
  if (!menuCache.menu) menuCache.init();
  const isOpen = menuCache.menu.style.display === 'block';

  if (isOpen) {
    closeMenu();
  } else {
    openMenu();
  }
}

function openMenu() {
  if (!menuCache.menu) menuCache.init();
  menuCache.menu.style.display = 'block';
  menuCache.menu.setAttribute('aria-hidden', 'false');

  // UPDATED: Now focuses on the first tool button instead of the Close button
  const firstOption = menuCache.menu.querySelector('.snn-accessibility-option');
  if (firstOption) {
    firstOption.focus();
  } else if (menuCache.closeButton) {
    menuCache.closeButton.focus();
  }

  // Add keyboard navigation
  document.addEventListener('keydown', handleMenuKeyboard);
}

function closeMenu() {
  if (!menuCache.menu) menuCache.init();
  menuCache.menu.style.display = 'none';
  menuCache.menu.setAttribute('aria-hidden', 'true');

  if (menuCache.button) {
    menuCache.button.focus();
  }

  // Remove keyboard navigation
  document.removeEventListener('keydown', handleMenuKeyboard);
}

// Cache for keyboard navigation elements
let keyboardCache = {
  focusableElements: null,
  lastUpdate: 0,
  getFocusableElements: function () {
    const now = Date.now();
    if (!this.focusableElements || now - this.lastUpdate > 1000) {
      if (menuCache.menu) {
        this.focusableElements = {
          all: menuCache.menu.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'),
          options: Array.from(menuCache.menu.querySelectorAll('.snn-accessibility-option, .snn-close, .snn-reset-button'))
        };
        this.lastUpdate = now;
      }
    }
    return this.focusableElements;
  }
};

function handleMenuKeyboard(e) {
  if (!menuCache.menu || menuCache.menu.style.display !== 'block') return;

  if (e.key === 'Escape') {
    e.preventDefault();
    closeMenu();
    return;
  }

  const elements = keyboardCache.getFocusableElements();
  if (!elements) return;

  if (e.key === 'Tab') {
    const firstElement = elements.all[0];
    const lastElement = elements.all[elements.all.length - 1];

    if (e.shiftKey) {
      if (document.activeElement === firstElement) {
        e.preventDefault();
        lastElement.focus();
      }
    } else {
      if (document.activeElement === lastElement) {
        e.preventDefault();
        firstElement.focus();
      }
    }
  }

  if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
    e.preventDefault();
    const currentIndex = elements.options.indexOf(shadowRoot.activeElement);
    let nextIndex;

    if (e.key === 'ArrowDown') {
      nextIndex = currentIndex === elements.options.length - 1 ? 0 : currentIndex + 1;
    } else {
      nextIndex = currentIndex === 0 ? elements.options.length - 1 : currentIndex - 1;
    }

    elements.options[nextIndex].focus();
  }
}

// ===========================================
// INITIALIZATION
// ===========================================

// Initialize the widget
function initAccessibilityWidget() {
  // Create shadow DOM first
  createShadowContainer();
  
  // Inject page styles (for accessibility features)
  injectPageStyles();
  
  // Apply saved settings
  applySettings();
  
  // Create widget UI inside shadow DOM
  createAccessibilityButton();
  createAccessibilityMenu();
}

// ===========================================
// WIDGET BOOTSTRAP
// ===========================================

// Load the widget when the DOM is fully loaded
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initAccessibilityWidget);
} else {
  initAccessibilityWidget();
}