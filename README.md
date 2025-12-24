# 🦖 KT (KaijuTranslator)

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.0-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![SEO Friendly](https://img.shields.io/badge/SEO-Optimized-orange.svg)](#)

**The simplest, "Dummy-Proof" Translation Engine for PHP Websites.** 🌍✨

KT is an isolated, plug-and-play engine that translates your entire PHP website into multiple languages in seconds using AI (OpenAI, DeepSeek, or Gemini). No complex routing, no database, no headache.

---

## 🚀 Why KT?

- **Zero Config Headache**: Drop it into your root, run the setup, and you're global.
- **Visual Dashboard**: A premium management console included. No terminal required after setup.
- **AI-Agnostic**: Use OpenAI (GPT-4o), DeepSeek, or Gemini. You choose your brain.
- **SEO Master**: Automatically generates `hreflang` tags, canonicals, and XML Sitemaps.
- **Stealth Mode**: 100% isolated. Delete the `KT` folder and your site is exactly as it was.

---

## 🛠️ Quick Start (3 Steps)

### 1. Upload

Drop the `KT/` folder, `setup.php`, and `uninstall.php` into your website's root directory.

### 2. Configure (Visual or CLI)

Open `yoursite.com/setup.php` in your browser or run:

```bash
php setup.php
```

Follow the wizard to set your languages and API Key.

### 3. Build & Thrive

Go to your **Premium Dashboard** at `yoursite.com/KT/dashboard.php` and click **"Build Stubs"**. KT will discover all your pages and create the language mirrors automatically.

---

## 💎 Premium Features

### 🎨 Visual Dashboard

KT comes with a stunning **Glassmorphism Dashboard** to:

- Monitor translation stats.
- Clear cache with one click.
- Trigger builds without touching a single line of code.

### 🛡️ Smart Fallback

If your API key expires or is missing, KT will **never break your site**. It will gracefully serve the original content while keeping all SEO tags intact.

### 🦖 Directory Structure

KT loves cleanliness. It strictly ignores its own files to keep your language folders (`/en/`, `/fr/`, etc.) pristine.

---

## 🤝 Contributing

KT is built for the community. If you have an idea to make it even more "Dummy-Proof", feel free to open a PR!

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---
**Build the global web, one PHP file at a time.** 🦖🌍
