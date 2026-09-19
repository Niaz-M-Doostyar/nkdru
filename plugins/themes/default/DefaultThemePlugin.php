<?php

/**
 * @file plugins/themes/default/DefaultThemePlugin.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DefaultThemePlugin
 * @brief Default theme
 */

namespace APP\plugins\themes\default;

use APP\core\Application;
use APP\file\PublicFileManager;
use PKP\config\Config;
use PKP\core\PKPSessionGuard;
use PKP\plugins\Hook;

class DefaultThemePlugin extends \PKP\plugins\ThemePlugin
{
    /**
     * @copydoc ThemePlugin::isActive()
     */
    public function isActive()
    {
        if (PKPSessionGuard::isSessionDisable()) {
            return true;
        }
        return parent::isActive();
    }

    /**
     * Initialize the theme's styles, scripts and hooks.
     */
    public function init()
    {
        // Register theme options
        $this->addOption('typography', 'FieldOptions', [
            'type' => 'radio',
            'label' => __('plugins.themes.default.option.typography.label'),
            'description' => __('plugins.themes.default.option.typography.description'),
            'options' => [
                ['value' => 'notoSans', 'label' => __('plugins.themes.default.option.typography.notoSans')],
                ['value' => 'notoSerif', 'label' => __('plugins.themes.default.option.typography.notoSerif')],
                ['value' => 'notoSerif_notoSans', 'label' => __('plugins.themes.default.option.typography.notoSerif_notoSans')],
                ['value' => 'notoSans_notoSerif', 'label' => __('plugins.themes.default.option.typography.notoSans_notoSerif')],
                ['value' => 'lato', 'label' => __('plugins.themes.default.option.typography.lato')],
                ['value' => 'lora', 'label' => __('plugins.themes.default.option.typography.lora')],
                ['value' => 'lora_openSans', 'label' => __('plugins.themes.default.option.typography.lora_openSans')],
            ],
            'default' => 'notoSans',
        ]);

        $this->addOption('baseColour', 'FieldColor', [
            'label' => __('plugins.themes.default.option.colour.label'),
            'description' => __('plugins.themes.default.option.colour.description'),
            'default' => '#1E6292',
        ]);

        $this->addOption('showDescriptionInJournalIndex', 'FieldOptions', [
            'label' => __('manager.setup.contextSummary'),
            'options' => [['value' => true, 'label' => __('plugins.themes.default.option.showDescriptionInJournalIndex.option')]],
            'default' => false,
        ]);

        $this->addOption('useHomepageImageAsHeader', 'FieldOptions', [
            'label' => __('plugins.themes.default.option.useHomepageImageAsHeader.label'),
            'description' => __('plugins.themes.default.option.useHomepageImageAsHeader.description'),
            'options' => [['value' => true, 'label' => __('plugins.themes.default.option.useHomepageImageAsHeader.option')]],
            'default' => false,
        ]);

        $this->addOption('displayStats', 'FieldOptions', [
            'type' => 'radio',
            'label' => __('plugins.themes.default.option.displayStats.label'),
            'options' => [
                ['value' => 'none', 'label' => __('plugins.themes.default.option.displayStats.none')],
                ['value' => 'bar', 'label' => __('plugins.themes.default.option.displayStats.bar')],
                ['value' => 'line', 'label' => __('plugins.themes.default.option.displayStats.line')],
            ],
            'default' => 'none',
        ]);

        // Load primary stylesheet
        $this->addStyle('stylesheet', 'styles/index.less');

        $additionalLessVariables = [];

        if ($this->getOption('typography') === 'notoSerif') {
            $this->addStyle('font', 'styles/fonts/notoSerif.less');
            $additionalLessVariables[] = '@font: "Noto Serif", -apple-system, BlinkMacSystemFont, "Segoe UI", "Roboto", "Oxygen-Sans", "Ubuntu", "Cantarell", "Helvetica Neue", sans-serif;';
        } elseif (strpos($this->getOption('typography'), 'notoSerif') !== false) {
            $this->addStyle('font', 'styles/fonts/notoSans_notoSerif.less');
            if ($this->getOption('typography') == 'notoSerif_notoSans') {
                $additionalLessVariables[] = '@font-heading: "Noto Serif", serif;';
            } elseif ($this->getOption('typography') == 'notoSans_notoSerif') {
                $additionalLessVariables[] = '@font: "Noto Serif", serif;@font-heading: "Noto Sans", serif;';
            }
        } elseif ($this->getOption('typography') == 'lato') {
            $this->addStyle('font', 'styles/fonts/lato.less');
            $additionalLessVariables[] = '@font: Lato, sans-serif;';
        } elseif ($this->getOption('typography') == 'lora') {
            $this->addStyle('font', 'styles/fonts/lora.less');
            $additionalLessVariables[] = '@font: Lora, serif;';
        } elseif ($this->getOption('typography') == 'lora_openSans') {
            $this->addStyle('font', 'styles/fonts/lora_openSans.less');
            $additionalLessVariables[] = '@font: "Open Sans", sans-serif;@font-heading: Lora, serif;';
        } else {
            $this->addStyle('font', 'styles/fonts/notoSans.less');
        }

        if (($baseColour = $this->getOption('baseColour')) !== '#1E6292') {
            if (!preg_match('/^#[0-9a-fA-F]{1,6}$/', $baseColour)) $baseColour = '#1E6292';
            $additionalLessVariables[] = '@bg-base:' . $baseColour . ';';
            if (!$this->isColourDark($baseColour)) {
                $additionalLessVariables[] = '@text-bg-base:rgba(0,0,0,0.84);';
                $additionalLessVariables[] = '@bg-base-border-color:rgba(0,0,0,0.2);';
            }
        }

        if (!empty($additionalLessVariables)) {
            $this->modifyStyle('stylesheet', ['addLessVariables' => join("\n", $additionalLessVariables)]);
        }

        $request = Application::get()->getRequest();

        $this->addStyle('fontAwesome', $request->getBaseUrl() . '/lib/pkp/styles/fontawesome/fontawesome.css', ['baseUrl' => '']);

        $context = Application::get()->getRequest()->getContext();
        if ($context && $this->getOption('useHomepageImageAsHeader') && ($homepageImage = $context->getLocalizedData('homepageImage'))) {
            $publicFileManager = new PublicFileManager();
            $publicFilesDir = $request->getBaseUrl() . '/' . $publicFileManager->getContextFilesPath($context->getId());
            $homepageImageUrl = $publicFilesDir . '/' . $homepageImage['uploadName'];
            $this->addStyle('homepageImage', '.pkp_structure_head { background: center / cover no-repeat url("' . $homepageImageUrl . '");}', ['inline' => true]);
        }

        $min = Config::getVar('general', 'enable_minified') ? '.min' : '';
        $jquery = $request->getBaseUrl() . '/js/build/jquery/jquery' . $min . '.js';
        $jqueryUI = $request->getBaseUrl() . '/js/build/jquery-ui/jquery-ui' . $min . '.js';
        $this->addScript('jQuery', $jquery, ['baseUrl' => '']);
        $this->addScript('jQueryUI', $jqueryUI, ['baseUrl' => '']);

        $this->addScript('popper', 'js/lib/popper/popper.js');
        $this->addScript('bsUtil', 'js/lib/bootstrap/util.js');
        $this->addScript('bsDropdown', 'js/lib/bootstrap/dropdown.js');

        $this->addScript('swiper', 'js/lib/swiper/swiper-bundle' . $min . '.js');
        $this->addStyle('swiper', 'js/lib/swiper/swiper-bundle' . $min . '.css');
        $this->addScript('swiper-i18n', $this->getSwiperI18n(), ['inline' => true]);

        $this->addScript('default', 'js/main.js');

        // ========================================
        // CUSTOM: Carousel hooks
        // ========================================
        // 1. Inject "Carousel Images" section into Settings → Website → Appearance
        Hook::add('TemplateManager::display', [$this, 'addCarouselToAppearance']);
        
        // 2. Load carousel images from DB for the homepage
        Hook::add('TemplateManager::display', [$this, 'addCarouselImages']);

        $this->addMenuArea(['primary', 'user']);
    }

        /**
     * DEBUG VERSION - Inject Carousel Manager tab
     */
    public function addCarouselToAppearance($hookName, $args)
    {
        // DEBUG: Log every time this hook fires
        $logFile = sys_get_temp_dir() . '/carousel-debug.log';
        file_put_contents($logFile, 
            date('Y-m-d H:i:s') . 
            ' | URI=' . ($_SERVER['REQUEST_URI'] ?? 'NONE') . 
            ' | TEMPLATE=' . ($args[1] ?? 'NONE') . 
            "\n", 
            FILE_APPEND
        );

        $templateMgr = $args[0];
        $template = $args[1];

        $request = Application::get()->getRequest();
        $context = $request->getContext();

        if (!$context) {
            return false;
        }

        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($requestUri, '/settings/website') === false) {
            return false;
        }

        $carouselUrl = $request->getDispatcher()->url(
            $request,
            \PKP\core\PKPApplication::ROUTE_PAGE,
            $context->getPath(),
            'carousel'
        );

        $js = '
        (function() {
            var carouselUrl = ' . json_encode($carouselUrl) . ';
            function injectCarouselTab() {
                if (document.getElementById("carousel-tab-button")) return true;
                var advancedBtn = document.getElementById("advanced-button");
                if (!advancedBtn) return false;
                var newBtn = document.createElement("a");
                newBtn.id = "carousel-tab-button";
                newBtn.className = "pkpTabs__button";
                newBtn.href = carouselUrl;
                newBtn.style.textDecoration = "none";
                newBtn.style.display = "inline-block";
                newBtn.innerHTML = "Carousel Images";
                advancedBtn.parentNode.insertBefore(newBtn, advancedBtn.nextSibling);
                return true;
            }
            if (injectCarouselTab()) return;
            var attempts = 0;
            var timer = setInterval(function() {
                attempts++;
                if (injectCarouselTab() || attempts > 50) clearInterval(timer);
            }, 200);
        })();
        ';

        // Add to the template as footer JS
        $templateMgr->addJavaScript(
            'carousel-injector',
            $js,
            ['inline' => true, 'contexts' => 'backend']
        );

        return false;
    }

    /**
     * Load carousel images from database for the homepage
     */
    public function addCarouselImages($hookName, $args)
    {
        $templateMgr = $args[0];
        $template = $args[1];

        if (strpos($template, 'indexJournal.tpl') !== false) {
            $request = Application::get()->getRequest();
            $context = $request->getContext();

            if ($context) {
                try {
                    $images = \Illuminate\Support\Facades\DB::table('carousel_slides')
                        ->where('journal_id', $context->getId())
                        ->where('status', 1)
                        ->orderBy('display_order', 'asc')
                        ->pluck('image')
                        ->toArray();

                    $publicFilesDir = Config::getVar('files', 'public_files_dir');
                    $carouselBaseUrl = $request->getBaseUrl() . '/' . $publicFilesDir . '/carousel/';

                    $templateMgr->assign('carouselImages', $images);
                    $templateMgr->assign('carouselBaseUrl', $carouselBaseUrl);
                } catch (\Exception $e) {
                    // Table not yet created - assign empty to prevent errors
                    $templateMgr->assign('carouselImages', []);
                    $templateMgr->assign('carouselBaseUrl', '');
                }
            }
        }

        return false;
    }

    public function getContextSpecificPluginSettingsFile()
    {
        return $this->getPluginPath() . '/settings.xml';
    }

    public function saveOption($name, $value, $contextId = null) {
        if ($name == 'baseColour' && !preg_match('/^#[0-9a-fA-F]{1,6}$/', $value)) $value = null;
        parent::saveOption($name, $value, $contextId);
    }

    public function getInstallSitePluginSettingsFile()
    {
        return $this->getPluginPath() . '/settings.xml';
    }

    public function getDisplayName()
    {
        return __('plugins.themes.default.name');
    }

    public function getDescription()
    {
        return __('plugins.themes.default.description');
    }

    public function getSwiperI18n(): string
    {
        return 'var pkpDefaultThemeI18N = ' . json_encode([
            'nextSlide' => __('plugins.themes.default.nextSlide'),
            'prevSlide' => __('plugins.themes.default.prevSlide'),
        ]);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\themes\default\DefaultThemePlugin', '\DefaultThemePlugin');
}