# 🦖 KaijuTranslator | The Global Engine

> **Visual Intelligence Update (v1.1.0)**: Featuring a new 5-column intelligence grid, instant widget preview, and recursive mapping.

KaijuTranslator is a self-hosted, AI-powered localization engine that turns any PHP/HTML website into a multilingual powerhouse without complex integrations or monthly subscription fees.

---

## 🚀 Key Features

### 🧠 AI Orchestration Grid

The new 5-column dashboard guides you through the localization lifecycle:

1. **Source Vector**: Analyzes your codebase `detect_origin`.
2. **Target Distribution**: Maps your content to 130+ languages.
3. **Neural Provider**: Connects to LLMs (OpenAI, Gemini, etc.) via API Key.
4. **volume Projection**: *[New]* Performs a deep recursive scan to calculate the exact build size (`Source Files * Target Languages`) before you start.
5. **Production Build**: Execution engine that generates static files.

### 🎨 Visual Widget Customizer

Create a language switcher that fits your brand perfectly.

- **Themes**: ✨ Modern Glass, ⚪ Minimal White, 🦖 Bold Kaiju, 🫧 Floating Bubble.
- **Content Modes**: Choose between **Flags Only**, **Text Only**, or **Both**.
- **Instant Preview**: See changes in real-time before deploying.
- **SVG Icons**: High-quality, scalable flag icons via `flagcdn`.

### 🌲 Recursive Mapping Intelligence

KaijuTranslator doesn't just translate pages; it rebuilds your entire directory structure.

- **Deep Scan**: Detects nested folders and assets.
- **Ghost Page Detection**: Compares your file system against your `sitemap.xml`.
- **Smart Sitemaps**: Automatically generates localized sitemaps for SEO.

---

## 🛠️ Installation

1. **Deploy**: Upload the `KT` folder to your server root.
2. **Access**: Navigate to `your-site.com/KT/index.php`.
3. **Secure**: Set an access password in `kaiju-config.php`.

## 📦 Usage

1. **Configure Languages**: Select your Base Language and Target Languages in the Global Matrix.
2. **Connect AI**: Enter your OpenAI (or compatible) API Key.
3. **Calculate Volume**: Use the "Calculate" button to scan your site and estimate the workload.
4. **Visual Setup**: Customize your widget in the "Widget Setup" tab.
5. **Build**: Go to the "Generation" tab and launch the translation process.
6. **Embed**: Add the following code to your footer:

    ```php
    <?php include 'KT/widget.php'; ?>
    ```

## 📝 License

Proprietary / Commercial.
