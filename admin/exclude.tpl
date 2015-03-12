<div class="titrePage">
  <h2>RV Autocomplete {$TABSHEET_TITLE}</h2>
</div>

<form action="{$U_FORM}" method="post">
<table>
<tr>
<td>{'Albums'|@translate}</td>
<td>{'Tags'|@translate}</td>
</tr>

<tr>
<td>
<select style="width:500px" name="excluded_albums[]" multiple="multiple" size="25">
  {html_options options=$albums selected=$albums_selected}
</select>
</td>
<td>
<select style="width:500px" name="excluded_tags[]" multiple="multiple" size="25">
  {html_options options=$tags selected=$tags_selected}
</select>
</td>
</tr>
</table>


<p>
<input type="submit" name="submit" value="{'Submit'|@translate}">
</p>
</form>