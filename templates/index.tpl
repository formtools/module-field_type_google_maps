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

  {include file="messages.tpl"}

  <div class="margin_bottom_large">
    This module doesn't have a configuration section. See the
    <a href="http://modules.formtools.org/field_type_google_maps/">online documentation</a>
    for more information.
  </div>

{include file='modules_footer.tpl'}