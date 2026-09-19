<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Carousel Manager</title>
    <link rel="stylesheet" href="{$baseUrl}/lib/pkp/styles/fontawesome/fontawesome.css">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f4f6f8;
            color: #222;
            margin: 0;
            padding: 0;
            line-height: 1.5;
        }
        .header { background: #1a5f7a; color: #fff; padding: 20px 30px; margin-bottom: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 1.6rem; }
        .header a { color: #ffdd99; text-decoration: none; font-size: 0.9rem; }
        .header a:hover { text-decoration: underline; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px 40px; }
        .card { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin-bottom: 25px; }
        .card h2 { margin-top: 0; color: #1a5f7a; border-bottom: 2px solid #e67e22; display: inline-block; padding-bottom: 6px; }
        .form-row { margin-bottom: 15px; }
        .form-row label { display: block; font-weight: 600; margin-bottom: 6px; }
        .form-row input[type="file"] { padding: 10px; border: 1px solid #ccc; border-radius: 6px; background: #fafafa; width: 100%; max-width: 500px; }
        .btn {
            display: inline-block;
            padding: 10px 22px;
            background: #1a5f7a;
            color: #fff;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }
        .btn:hover { background: #e67e22; color: #fff; text-decoration: none; }
        .btn-danger { background: #ff4040; padding: 6px 14px; font-size: 0.8rem; }
        .btn-danger:hover { background: #cc0000; }
        .btn-toggle { padding: 5px 12px; border-radius: 15px; font-size: 0.8rem; text-decoration: none; color: #fff; display: inline-block; }
        .btn-toggle:hover { opacity: 0.85; text-decoration: none; color: #fff; }
        .btn-toggle.active { background: #00b24e; }
        .btn-toggle.inactive { background: #999; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th { text-align: left; padding: 12px; background: #f0f4f8; border-bottom: 2px solid #ddd; font-weight: 600; }
        td { padding: 15px 12px; border-bottom: 1px solid #eee; vertical-align: middle; }
        tr:hover { background: #fafafa; }
        .thumb { width: 200px; height: 70px; object-fit: cover; border-radius: 6px; box-shadow: 0 1px 4px rgba(0,0,0,0.1); }
        .order-input { width: 60px; padding: 6px; text-align: center; border: 1px solid #ccc; border-radius: 4px; }
        .empty { text-align: center; padding: 60px 20px; color: #999; }
        .empty .fa { font-size: 48px; display: block; margin-bottom: 15px; color: #ddd; }
        .notifications { margin-bottom: 20px; }
        .notification { padding: 12px 18px; border-radius: 6px; margin-bottom: 10px; }
        .notification.success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .notification.error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
    </style>
</head>
<body>

<div class="header">
    <h1><i class="fa fa-image"></i> Carousel Manager</h1>
    <a href="{$baseUrl}/index.php/{$currentContext->getPath()}/management/settings/website">
        <i class="fa fa-arrow-left"></i> Back to Website Settings
    </a>
</div>

<div class="container">

    {if $successMessage}
        <div class="notifications">
            <div class="notification success">{$successMessage}</div>
        </div>
    {/if}

    {if $errorMessage}
        <div class="notifications">
            <div class="notification error">{$errorMessage}</div>
        </div>
    {/if}

    <div class="card">
        <h2>Add New Image</h2>
        <form action="{$baseUrl}/index.php/{$currentContext->getPath()}/carousel/upload" method="post" enctype="multipart/form-data">
            <div class="form-row">
                <label for="carousel_image">Select Image</label>
                <input type="file" name="carousel_image" id="carousel_image" accept="image/jpeg,image/png,image/webp" required>
                <p style="color:#666;font-size:0.85rem;margin:6px 0 0;">Accepted: JPG, PNG, WebP. Maximum: 5MB.</p>
            </div>
            <button type="submit" class="btn"><i class="fa fa-upload"></i> Upload Image</button>
        </form>
    </div>

    {if $slides|@count > 0}
        <div class="card">
            <h2>Current Slides ({$slides|@count})</h2>
            <form action="{$baseUrl}/index.php/{$currentContext->getPath()}/carousel/reorder" method="post">
                <table>
                    <thead>
                        <tr>
                            <th style="width:70px;">Order</th>
                            <th style="width:230px;">Preview</th>
                            <th>Filename</th>
                            <th style="width:100px;">Status</th>
                            <th style="width:100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$slides item=slide}
                            <tr style="{if $slide->status == 0}opacity:0.55;{/if}">
                                <td>
                                    <input type="number" name="order[{$slide->id}]" value="{$slide->display_order}" class="order-input" min="0">
                                </td>
                                <td>
                                    <img src="{$imageUrl}{$slide->image|escape:'url'}" class="thumb" alt="">
                                </td>
                                <td>
                                    <code style="font-size:0.85rem;">{$slide->image|escape}</code><br>
                                    <small style="color:#999;">{$slide->created_at}</small>
                                </td>
                                <td>
                                    <a href="{$baseUrl}/index.php/{$currentContext->getPath()}/carousel/toggle/{$slide->id}"
                                       class="btn-toggle {if $slide->status == 1}active{else}inactive{/if}">
                                        {if $slide->status == 1}Active{else}Inactive{/if}
                                    </a>
                                </td>
                                <td>
                                    <a href="{$baseUrl}/index.php/{$currentContext->getPath()}/carousel/delete/{$slide->id}"
                                       class="btn btn-danger"
                                       onclick="return confirm('Delete this image? This cannot be undone.');">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
                <div style="margin-top:20px;">
                    <button type="submit" class="btn"><i class="fa fa-save"></i> Save Order</button>
                    <span style="margin-left:12px;color:#666;font-size:0.9rem;">Update order numbers and click Save.</span>
                </div>
            </form>
        </div>
    {else}
        <div class="card empty">
            <i class="fa fa-image"></i>
            <h2 style="color:#999;border:none;">No Images Yet</h2>
            <p>Upload your first carousel image above to get started.</p>
        </div>
    {/if}

</div>

</body>
</html>