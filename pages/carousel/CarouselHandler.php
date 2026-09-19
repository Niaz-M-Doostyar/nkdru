<?php

/**
 * @file pages/carousel/CarouselHandler.php
 */

namespace APP\pages\carousel;

use APP\core\Application;
use APP\handler\Handler;
use APP\template\TemplateManager;
use APP\notification\NotificationManager;
use PKP\config\Config;
use PKP\security\Role;
use PKP\notification\Notification;
use PKP\security\authorization\ContextRequiredPolicy;
use Illuminate\Support\Facades\DB;

class CarouselHandler extends Handler
{
    private string $_imageDir;
    private string $_imageUrl;

    public function __construct()
    {
        parent::__construct();

        $request = Application::get()->getRequest();
        $publicFilesDir = Config::getVar('files', 'public_files_dir');

        // Store inside OJS root /public folder (directly web-accessible)
        $ojsRoot = dirname(__DIR__, 2); // Goes up from pages/carousel/ to OJS root
        $this->_imageDir = $ojsRoot . DIRECTORY_SEPARATOR . $publicFilesDir . DIRECTORY_SEPARATOR . 'carousel' . DIRECTORY_SEPARATOR;
        $this->_imageUrl = $request->getBaseUrl() . '/' . $publicFilesDir . '/carousel/';

        if (!is_dir($this->_imageDir)) {
            mkdir($this->_imageDir, 0755, true);
        }
    }

    public function authorize($request, &$args, $roleAssignments)
    {
        $this->addPolicy(new ContextRequiredPolicy($request));

        $roleAssignments = [
            Role::ROLE_ID_MANAGER => ['index', 'upload', 'delete', 'toggle', 'reorder'],
            Role::ROLE_ID_SITE_ADMIN => ['index', 'upload', 'delete', 'toggle', 'reorder'],
        ];

        return parent::authorize($request, $args, $roleAssignments);
    }

    public function index($args, $request)
    {
        $context = $request->getContext();
        $journalId = $context->getId();

        try {
            $slides = DB::table('carousel_slides')
                ->where('journal_id', $journalId)
                ->orderBy('display_order', 'asc')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            $slides = [];
        }

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign([
            'slides' => $slides,
            'imageUrl' => $this->_imageUrl,
            'pageTitle' => 'Carousel Manager',
            'baseUrl' => $request->getBaseUrl(),
            'currentContext' => $context,
        ]);

        $templateMgr->display('carousel/index.tpl');
    }

    public function upload($args, $request)
    {
        $context = $request->getContext();
        $journalId = $context->getId();

        if (!$request->isPost()) {
            $request->redirect(null, 'carousel');
            return;
        }

        $uploadedFile = $_FILES['carousel_image'] ?? null;

        if (!$uploadedFile || $uploadedFile['error'] !== UPLOAD_ERR_OK) {
            $this->_notify($request, 'Error uploading file.', false);
            $request->redirect(null, 'carousel');
            return;
        }

        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $fileType = finfo_file($finfo, $uploadedFile['tmp_name']);
        finfo_close($finfo);

        if (!isset($allowed[$fileType])) {
            $this->_notify($request, 'Only JPG, PNG, and WebP allowed.', false);
            $request->redirect(null, 'carousel');
            return;
        }

        if ($uploadedFile['size'] > 5 * 1024 * 1024) {
            $this->_notify($request, 'Image must be less than 5MB.', false);
            $request->redirect(null, 'carousel');
            return;
        }

        $ext = $allowed[$fileType];
        $filename = 'carousel_' . time() . '_' . substr(md5(uniqid()), 0, 8) . '.' . $ext;
        $destination = $this->_imageDir . $filename;

        if (!move_uploaded_file($uploadedFile['tmp_name'], $destination)) {
            $this->_notify($request, 'Failed to save image.', false);
            $request->redirect(null, 'carousel');
            return;
        }

        $maxOrder = DB::table('carousel_slides')
            ->where('journal_id', $journalId)
            ->max('display_order') ?? 0;

        DB::table('carousel_slides')->insert([
            'journal_id' => $journalId,
            'image' => $filename,
            'display_order' => $maxOrder + 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->_notify($request, 'Image uploaded!', true);
        $request->redirect(null, 'carousel');
    }

    public function delete($args, $request)
    {
        $context = $request->getContext();
        $journalId = $context->getId();
        $slideId = (int) ($args[0] ?? $request->getUserVar('id') ?? 0);

        if ($slideId) {
            $slide = DB::table('carousel_slides')
                ->where('id', $slideId)
                ->where('journal_id', $journalId)
                ->first();

            if ($slide) {
                $filePath = $this->_imageDir . $slide->image;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }

                DB::table('carousel_slides')
                    ->where('id', $slideId)
                    ->where('journal_id', $journalId)
                    ->delete();

                $this->_notify($request, 'Image deleted.', true);
            }
        }

        $request->redirect(null, 'carousel');
    }

    public function toggle($args, $request)
    {
        $context = $request->getContext();
        $journalId = $context->getId();
        $slideId = (int) ($args[0] ?? $request->getUserVar('id') ?? 0);

        if ($slideId) {
            DB::table('carousel_slides')
                ->where('id', $slideId)
                ->where('journal_id', $journalId)
                ->update([
                    'status' => DB::raw('CASE WHEN status = 1 THEN 0 ELSE 1 END'),
                    'updated_at' => now(),
                ]);
        }

        $request->redirect(null, 'carousel');
    }

    public function reorder($args, $request)
    {
        $context = $request->getContext();
        $journalId = $context->getId();

        if ($request->isPost()) {
            $order = $request->getUserVar('order');
            if (is_array($order)) {
                foreach ($order as $slideId => $position) {
                    DB::table('carousel_slides')
                        ->where('id', (int) $slideId)
                        ->where('journal_id', $journalId)
                        ->update([
                            'display_order' => (int) $position,
                            'updated_at' => now(),
                        ]);
                }
                $this->_notify($request, 'Order updated.', true);
            }
        }

        $request->redirect(null, 'carousel');
    }

    private function _notify($request, string $message, bool $isSuccess = true): void
    {
        $notificationManager = new NotificationManager();
        $notificationManager->createTrivialNotification(
            $request->getUser()->getId(),
            $isSuccess ? Notification::NOTIFICATION_TYPE_SUCCESS : Notification::NOTIFICATION_TYPE_ERROR,
            ['contents' => $message]
        );
    }
}