{include file='modules_header.tpl'}

    <table cellpadding="0" cellspacing="0">
    <tr>
        <td width="45"><img src="images/google_maps_icon.png" width="34" height="34" /></td>
        <td class="title">
            <a href="../../admin/modules">{$LANG.word_modules}</a>
            <span class="joiner">&raquo;</span>
            {$L.module_name}
        </td>
    </tr>
    </table>

    {ft_include file="messages.tpl"}

    <div class="margin_bottom_large">
        Please enter your Google Maps v3 API key below. Google requires this field to allow you to add maps to your
        website. [<a href="https://developers.google.com/maps/documentation/javascript/get-api-key">Get a google maps key here</a>].
    </div>

    <form action="./" method="post">
        <div class="margin_bottom_large">
            <input type="text" name="google_maps_key" placeholder="Enter Google Maps API v3 key"
                style="padding:5px; width: 300px" autofocus value="{$google_maps_key}"/>
            <input type="submit" name="update" value="Update" />
        </div>
    </form>
    <div class="margin_bottom_large">
        See the <a href="https://docs.formtools.org/modules/field_type_google_maps/">module documentation</a>
        for more information on this module.
    </div>

{include file='modules_footer.tpl'}
