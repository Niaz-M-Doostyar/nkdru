{**
 * templates/carousel/index.tpl
 *
 * Carousel Manager
 *}
{include file="common/header.tpl"}

<div class="pkp_page_content pkp_page_carousel">

    <h1>Carousel Manager</h1>

    {* Upload Form *}
    <div style="background:#fff;padding:20px;border-radius:8px;margin-bottom:30px;border:1px solid #ddd;">
        <h2>Add New Carousel Image</h2>
        <form action="{url op="upload"}" method="post" enctype="multipart/form-data">
            <table class="data">
                <tr>
                    <td class="label"><label for="carousel_image">Select Image</label></td>
                    <td class="value">
                        <input type="file" name="carousel_image" id="carousel_image" 
                               accept="image/jpeg,image/png,image/webp" required>
                        <div class="description">Accepted formats: JPG, PNG, WebP. Maximum size: 5MB.</div>
                    </td>
                </tr>
            </table>
            <p>
                <button type="submit" class="submitFormButton">Upload Image</button>
            </p>
        </form>
    </div>

    {* Slides List *}
    {if $slides|@count > 0}
        <h2>Current Slides ({$slides|@count})</h2>

        <form action="{url op="reorder"}" method="post">
            <table class="listing" width="100%">
                <thead>
                    <tr>
                        <th width="60">Order</th>
                        <th width="250">Preview</th>
                        <th>Filename</th>
                        <th width="80">Status</th>
                        <th width="60">Action</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$slides item=slide}
                        <tr style="{if $slide.status == 0}opacity:0.5;{/if}">
                            <td align="center">
                                <input type="number" name="order[{$slide.id}]" value="{$slide.display_order}" 
                                       style="width:50px;text-align:center;" min="0">
                            </td>
                            <td>
                                <img src="{$imageUrl}{$slide.image|escape:"url"}" 
                                     style="width:220px;height:70px;object-fit:cover;border-radius:4px;" alt="">
                            </td>
                            <td>
                                <code>{$slide.image|escape}</code>
                                <br>
                                <small style="color:#999;">Added: {$slide.created_at|date_format:"%Y-%m-%d"}</small>
                            </td>
                            <td align="center">
                                <a href="{url op="toggle" path=$slide.id}"
                                   style="display:inline-block;padding:4px 10px;border-radius:12px;text-decoration:none;font-size:12px;
                                          background:{if $slide.status == 1}#00b24e{else}#ccc{/if};color:#fff;">
                                    {if $slide.status == 1}Active{else}Inactive{/if}
                                </a>
                            </td>
                            <td align="center">
                                <a href="{url op="delete" path=$slide.id}" 
                                   onclick="return confirm('Are you sure you want to delete this carousel image? This cannot be undone.');"
                                   style="color:#ff4040;font-weight:bold;text-decoration:none;">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
            <p>
                <button type="submit" class="submitFormButton">Save Order</button>
                <span style="margin-left:10px;color:#999;">Update order numbers and click Save.</span>
            </p>
        </form>
    {else}
        <div style="text-align:center;padding:60px;background:#fff;border-radius:8px;border:1px solid #ddd;">
            <span class="fa fa-image" style="font-size:48px;color:#ccc;display:block;margin-bottom:15px;"></span>
            <h2 style="color:#999;">No Carousel Images Yet</h2>
            <p style="color:#999;">Upload your first image using the form above to get started.</p>
        </div>
    {/if}

</div>

{include file="common/footer.tpl"}