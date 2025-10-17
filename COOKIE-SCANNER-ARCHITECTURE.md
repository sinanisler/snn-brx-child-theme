# Cookie Banner Page Scanner - System Architecture

## 🏗️ System Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                          ADMIN INTERFACE                            │
│  ┌───────────────────────────────────────────────────────────────┐ │
│  │  Page Scanner Tab                                             │ │
│  │  ┌──────────────────┐                                         │ │
│  │  │ Select Page      │ ──→ [Datalist: All Published Pages]    │ │
│  │  └──────────────────┘                                         │ │
│  │           ↓                                                   │ │
│  │  ┌──────────────────┐                                         │ │
│  │  │  Scan Button     │ ──→ AJAX Request                       │ │
│  │  └──────────────────┘                                         │ │
│  └───────────────────────────────────────────────────────────────┘ │
└────────────────────────────────┬────────────────────────────────────┘
                                 │
                                 ↓
┌─────────────────────────────────────────────────────────────────────┐
│                        BACKEND PROCESSING                           │
│  ┌───────────────────────────────────────────────────────────────┐ │
│  │  snn_scan_page_scripts_ajax()                                 │ │
│  │  ┌──────────────────────────────────────────────────────────┐│ │
│  │  │ 1. Verify nonce & permissions                            ││ │
│  │  │ 2. Fetch page HTML (wp_remote_get)                       ││ │
│  │  │ 3. Parse with DOMDocument                                ││ │
│  │  │ 4. Extract <script src> tags                             ││ │
│  │  │ 5. Extract <iframe src> tags                             ││ │
│  │  │ 6. Normalize URLs (relative → absolute)                  ││ │
│  │  │ 7. Return JSON response                                  ││ │
│  │  └──────────────────────────────────────────────────────────┘│ │
│  └───────────────────────────────────────────────────────────────┘ │
└────────────────────────────────┬────────────────────────────────────┘
                                 │
                                 ↓
┌─────────────────────────────────────────────────────────────────────┐
│                      RESULTS DISPLAY                                │
│  ┌───────────────────────────────────────────────────────────────┐ │
│  │  Display Scripts & Iframes                                    │ │
│  │  ┌────────────────────────────────────────────────────────┐  │ │
│  │  │ ☐ https://analytics.google.com/script.js               │  │ │
│  │  │ ☐ https://facebook.com/pixel.js                        │  │ │
│  │  │ ☑ https://ads.example.com/track.js (Already Blocked)   │  │ │
│  │  └────────────────────────────────────────────────────────┘  │ │
│  │           ↓                                                   │ │
│  │  [Block Selected Scripts]                                     │ │
│  └───────────────────────────────────────────────────────────────┘ │
└────────────────────────────────┬────────────────────────────────────┘
                                 │
                                 ↓
┌─────────────────────────────────────────────────────────────────────┐
│                      DATABASE STORAGE                               │
│  ┌───────────────────────────────────────────────────────────────┐ │
│  │  wp_options table                                             │ │
│  │  ┌────────────────────────────────────────────────────────┐  │ │
│  │  │ snn_cookie_settings_options                            │  │ │
│  │  │ {                                                      │  │ │
│  │  │   "snn_cookie_settings_blocked_scripts": [            │  │ │
│  │  │     "https://analytics.google.com/script.js",         │  │ │
│  │  │     "https://facebook.com/pixel.js"                   │  │ │
│  │  │   ]                                                    │  │ │
│  │  │ }                                                      │  │ │
│  │  └────────────────────────────────────────────────────────┘  │ │
│  └───────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────┘
```

## 🌐 Frontend Execution Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                        PAGE LOAD SEQUENCE                           │
└─────────────────────────────────────────────────────────────────────┘

1. Browser Starts Loading Page
   │
   ↓
2. wp_head Hook (Priority 1) ───→ snn_output_script_blocker()
   │                               │
   │                               ├─ Inject blocking script
   │                               ├─ Define blockedScripts array
   │                               ├─ Define isBlocked() function
   │                               └─ Setup MutationObserver
   ↓
3. HTML Continues Loading
   │
   ├─ <script src="analytics.js"> ──→ Blocked! (type="text/plain")
   ├─ <script src="facebook.js">  ──→ Blocked! (type="text/plain")
   ├─ <iframe src="youtube.com">  ──→ Blocked! (src removed)
   │
   ↓
4. DOMContentLoaded Event
   │
   ├─ Block existing scripts/iframes
   ├─ Start MutationObserver
   │
   ↓
5. Dynamic Scripts Added (AJAX, etc.)
   │
   └─ MutationObserver detects ──→ Blocks immediately
   │
   ↓
6. wp_footer Hook ───→ Cookie Banner Displayed
   │
   ↓
7. User Interaction
   │
   ├─ [Accept] ──→ unblockScripts() ──→ Scripts load
   │                                  ├─ Change type to "text/javascript"
   │                                  ├─ Restore iframe src
   │                                  └─ Disconnect observer
   │
   ├─ [Deny] ───→ Scripts stay blocked
   │
   └─ [Preferences] ──→ Custom selection ──→ Partial unblock
```

## 🔄 Script Blocking Mechanism

```
┌─────────────────────────────────────────────────────────────────────┐
│                    BEFORE CONSENT                                   │
│  ┌───────────────────────────────────────────────────────────────┐ │
│  │  Original HTML:                                               │ │
│  │  <script src="https://analytics.com/ga.js"></script>         │ │
│  └───────────────────────────────────────────────────────────────┘ │
│                              ↓                                      │
│                    [Script Blocker Runs]                            │
│                              ↓                                      │
│  ┌───────────────────────────────────────────────────────────────┐ │
│  │  Modified HTML:                                               │ │
│  │  <script type="text/plain"                                    │ │
│  │          data-snn-blocked="true"                              │ │
│  │          src="https://analytics.com/ga.js"></script>          │ │
│  └───────────────────────────────────────────────────────────────┘ │
│                              ↓                                      │
│                    [Browser Ignores Script]                         │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                    AFTER CONSENT                                    │
│  ┌───────────────────────────────────────────────────────────────┐ │
│  │  Blocked HTML:                                                │ │
│  │  <script type="text/plain"                                    │ │
│  │          data-snn-blocked="true"                              │ │
│  │          src="https://analytics.com/ga.js"></script>          │ │
│  └───────────────────────────────────────────────────────────────┘ │
│                              ↓                                      │
│                    [unblockScripts() Runs]                          │
│                              ↓                                      │
│  ┌───────────────────────────────────────────────────────────────┐ │
│  │  New HTML:                                                    │ │
│  │  <script type="text/javascript"                               │ │
│  │          src="https://analytics.com/ga.js"></script>          │ │
│  └───────────────────────────────────────────────────────────────┘ │
│                              ↓                                      │
│                    [Browser Executes Script]                        │
└─────────────────────────────────────────────────────────────────────┘
```

## 🎯 MutationObserver Pattern

```
┌─────────────────────────────────────────────────────────────────────┐
│                   MUTATION OBSERVER WORKFLOW                        │
└─────────────────────────────────────────────────────────────────────┘

Setup Phase:
┌────────────────────────────────────┐
│ var observer = new                 │
│   MutationObserver(callback);      │
│                                    │
│ observer.observe(                  │
│   document.documentElement, {      │
│     childList: true,               │
│     subtree: true                  │
│   }                                │
│ );                                 │
└────────────────────────────────────┘
         │
         ↓
Monitoring Phase:
┌────────────────────────────────────┐
│ DOM Change Detected                │
│   ↓                                │
│ New <script> added?                │
│   ↓ Yes                            │
│ Is it blocked?                     │
│   ↓ Yes                            │
│ Change type to "text/plain"        │
│ Add data-snn-blocked="true"        │
└────────────────────────────────────┘
         │
         ↓
Cleanup Phase:
┌────────────────────────────────────┐
│ User accepts cookies               │
│   ↓                                │
│ observer.disconnect()              │
│   ↓                                │
│ Allow all scripts to load          │
└────────────────────────────────────┘
```

## 💾 Data Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                        DATA FLOW DIAGRAM                            │
└─────────────────────────────────────────────────────────────────────┘

Admin Scans Page
      ↓
┌──────────────────┐
│  Page URL        │
└──────────────────┘
      ↓
┌──────────────────┐
│  AJAX Request    │───→ Security: Nonce + Capability Check
└──────────────────┘
      ↓
┌──────────────────┐
│  Fetch HTML      │───→ wp_remote_get($url)
└──────────────────┘
      ↓
┌──────────────────┐
│  Parse DOM       │───→ DOMDocument->loadHTML()
└──────────────────┘
      ↓
┌──────────────────┐
│  Extract URLs    │───→ getElementsByTagName('script')
│                  │───→ getElementsByTagName('iframe')
└──────────────────┘
      ↓
┌──────────────────┐
│  Normalize URLs  │───→ Relative → Absolute
└──────────────────┘
      ↓
┌──────────────────┐
│  JSON Response   │───→ { scripts: [], iframes: [] }
└──────────────────┘
      ↓
┌──────────────────┐
│  Display Results │
└──────────────────┘
      ↓
┌──────────────────┐
│  User Selects    │
└──────────────────┘
      ↓
┌──────────────────┐
│  Form Submission │───→ POST data
└──────────────────┘
      ↓
┌──────────────────┐
│  Save to DB      │───→ update_option()
└──────────────────┘
      ↓
┌──────────────────┐
│  Frontend Load   │───→ get_option()
└──────────────────┘
      ↓
┌──────────────────┐
│  Block Scripts   │───→ type="text/plain"
└──────────────────┘
```

## 🔐 Security Layer

```
┌─────────────────────────────────────────────────────────────────────┐
│                        SECURITY CHECKS                              │
└─────────────────────────────────────────────────────────────────────┘

Admin Request
      ↓
┌──────────────────────────────────────┐
│ 1. Nonce Verification                │
│    check_ajax_referer()              │
│    ✓ Valid  │  ✗ Invalid → Die       │
└──────────────────────────────────────┘
      ↓
┌──────────────────────────────────────┐
│ 2. Capability Check                  │
│    current_user_can('manage_options')│
│    ✓ Yes    │  ✗ No → Die            │
└──────────────────────────────────────┘
      ↓
┌──────────────────────────────────────┐
│ 3. URL Sanitization                  │
│    esc_url_raw()                     │
│    ✓ Clean  │  ✗ Invalid → Die       │
└──────────────────────────────────────┘
      ↓
┌──────────────────────────────────────┐
│ 4. Input Validation                  │
│    Check URL not empty               │
│    ✓ Valid  │  ✗ Empty → Error       │
└──────────────────────────────────────┘
      ↓
┌──────────────────────────────────────┐
│ 5. Output Escaping                   │
│    esc_html(), esc_attr(), esc_url() │
│    ✓ Always applied                  │
└──────────────────────────────────────┘
      ↓
┌──────────────────────────────────────┐
│ 6. XSS Prevention                    │
│    Script content base64 encoded     │
│    ✓ Safe for storage                │
└──────────────────────────────────────┘
```

## 📊 Performance Timeline

```
┌─────────────────────────────────────────────────────────────────────┐
│                     PAGE LOAD TIMELINE                              │
└─────────────────────────────────────────────────────────────────────┘

0ms    │ ▓ HTML starts loading
       │
1ms    │ ▓ Script blocker injected (wp_head priority 1)
       │   └─ ~5KB JavaScript
       │   └─ Execution time: <1ms
       │
2ms    │ ▓ blockedScripts array created
       │ ▓ isBlocked() function defined
       │
10ms   │ ▓ HTML continues loading
       │ ▓ Blocked scripts detected and type changed
       │
50ms   │ ▓ DOMContentLoaded
       │ ▓ MutationObserver started
       │
100ms  │ ▓ Page fully loaded
       │
500ms  │ ▓ Dynamic content loaded (AJAX)
       │ ▓ MutationObserver catches new scripts
       │ ▓ New scripts blocked immediately
       │
5000ms │ ▓ User clicks "Accept"
       │ ▓ unblockScripts() runs
       │ ▓ Scripts recreated and executed
       │ ▓ Observer disconnected
       │
5100ms │ ▓ Analytics starts tracking
       │
```

## 🔄 State Machine

```
┌─────────────────────────────────────────────────────────────────────┐
│                        BANNER STATE MACHINE                         │
└─────────────────────────────────────────────────────────────────────┘

           ┌──────────────────┐
           │  Initial State   │
           │  (No Cookie)     │
           └────────┬─────────┘
                    │
                    ↓
           ┌──────────────────┐
           │  Banner Visible  │
           │  Scripts Blocked │
           └────────┬─────────┘
                    │
        ┌───────────┼───────────┐
        │           │           │
        ↓           ↓           ↓
   [Accept]      [Deny]   [Preferences]
        │           │           │
        ↓           ↓           ↓
   ┌─────────┐ ┌─────────┐ ┌──────────┐
   │All      │ │None     │ │Custom    │
   │Allowed  │ │Allowed  │ │Selection │
   └────┬────┘ └────┬────┘ └─────┬────┘
        │           │            │
        └───────────┴────────────┘
                    │
                    ↓
           ┌──────────────────┐
           │  Cookie Set      │
           │  Banner Hidden   │
           └────────┬─────────┘
                    │
                    ↓
           ┌──────────────────┐
           │  Future Visits   │
           │  State Preserved │
           └──────────────────┘
```

## 🎨 Component Hierarchy

```
┌─────────────────────────────────────────────────────────────────────┐
│                    COMPONENT STRUCTURE                              │
└─────────────────────────────────────────────────────────────────────┘

cookie-banner.php
│
├── Admin Interface
│   ├── General Settings Tab
│   │   ├── Enable/Disable Banner
│   │   ├── Button Text Fields
│   │   └── Description Fields
│   │
│   ├── Scripts & Services Tab
│   │   ├── Service Repeater
│   │   └── Script Management
│   │
│   ├── Page Scanner Tab (NEW)
│   │   ├── Page Selection (Datalist)
│   │   ├── Scan Button
│   │   ├── Results Display
│   │   └── Blocked Scripts List
│   │
│   └── Styles & Layout Tab
│       ├── Colors
│       ├── Positioning
│       └── Custom CSS
│
├── Backend Functions
│   ├── snn_scan_page_scripts_ajax()
│   ├── snn_is_cookie_banner_enabled()
│   └── snn_add_cookie_settings_submenu()
│
├── Frontend Output
│   ├── snn_output_script_blocker()
│   │   └── Injected in wp_head (priority 1)
│   │
│   ├── snn_output_cookie_banner()
│   │   └── Injected in wp_footer
│   │
│   ├── snn_output_service_scripts()
│   │   └── Injected in wp_footer (priority 99)
│   │
│   ├── snn_output_banner_js()
│   │   └── Injected in wp_footer (priority 100)
│   │
│   └── snn_output_custom_css()
│       └── Injected in wp_footer (priority 999)
│
└── JavaScript Functions
    ├── Blocking Functions
    │   ├── isBlocked(url)
    │   ├── MutationObserver callback
    │   └── Block on DOMContentLoaded
    │
    ├── Unblocking Functions
    │   ├── unblockScripts()
    │   └── Recreate script elements
    │
    └── Consent Functions
        ├── Accept handler
        ├── Deny handler
        └── Preferences handler
```

## 🌊 Event Flow

```
┌─────────────────────────────────────────────────────────────────────┐
│                        EVENT FLOW                                   │
└─────────────────────────────────────────────────────────────────────┘

WordPress Hooks Execution Order:

1. init
   └─ (Plugin initialization)

2. wp_head (priority 1)
   └─ snn_output_script_blocker()
      ├─ Inject blocking script
      └─ Setup MutationObserver

3. wp_head (default priority)
   └─ Other theme/plugin scripts

4. wp_footer (default priority)
   └─ snn_output_cookie_banner()
      └─ Output banner HTML

5. wp_footer (priority 99)
   └─ snn_output_service_scripts()
      └─ Output service definitions

6. wp_footer (priority 100)
   └─ snn_output_banner_js()
      └─ Output banner interaction logic

7. wp_footer (priority 999)
   └─ snn_output_custom_css()
      └─ Output custom styles

Browser Events:

1. DOMContentLoaded
   └─ Block existing scripts/iframes

2. User Click Event
   ├─ Accept → unblockScripts() → Load all
   ├─ Deny → Keep blocked → Load none
   └─ Preferences → Toggle visibility

3. Mutation Events (continuous)
   └─ New nodes added → Check & block if needed
```

---

**Diagram Version**: 1.0.0  
**Last Updated**: October 17, 2025  
**Format**: ASCII Art / Text Diagrams
