<div class="titrePage">
  <h2>RV Autocomplete {$TABSHEET_TITLE}</h2>
</div>

<div id="acFormContainer" style="text-align:left">
<form>
<table>
<tr>
<td>
	<label for=ac_name style="min-width:100px">{'Query'|@translate}:</label>
</td>
<td>
	<input id="ac_name" type="text" size="48">
</td>
</tr>
<tr>
<td colspan=2>
<small>{'Use <b>\</b> to break search (e.g. Central Park\, NY will not match NY but it will display it)'|@translate}
</small>
</td>
</tr>


<tr>
<td>
<label for=ac_counter>{'Weight'|@translate}:</label>
</td>
<td>
	<input id="ac_counter" type="text" size="3">
</td>
</tr>

<tr>
<td>
<label for=ac_level>{'Privacy level'|@translate}:</label>
</td>
<td>
	<select id="ac_level"> 
	{foreach $available_permission_levels as $level=>$label}
		<option value="{$level}">{$label}</option>
	{/foreach}
	</select>
</td>
</tr>

<tr>
<td>
<label for=ac_url>{'Url'|@translate}:</label>
</td>
<td>
	<input id="ac_url" type="text" size="48">
</td>
</tr>

<tr>
<td colspan=2>
<small>{'Leave empty for automatic gallery search <br>OR enter an url ($r/ at the start of url means this gallery)<br>OR enter q=Term to search for a specific term'|@translate}
</small>
</td>
</tr>
</table>
</form>
</div>

<p><a class="openAddNew">{'Create a new suggestion'|@translate}</a></p>
<table id="csTable" class="table2">
<thead>
<tr class="throw">
<th class="dtc_Name">{'Name'|@translate}</th>
<th class="dtc_Weight">{'Weight'|@translate}</th>
<th class="dtc_Level">{'Privacy level'|@translate}</th>
<th class="dtc_Url">{'Url'|@translate}</th>
<th class="dtc_Actions">{'Actions'|@translate}</th>
</tr>
</thead>
<tbody>
</tbody>
</table>

<p><a class="openAddNew">{'Create a new suggestion'|@translate}</a></p>

{include file='include/colorbox.inc.tpl'}

{combine_css path="themes/default/js/ui/theme/jquery.ui.core.css"}
{combine_css path="themes/default/js/ui/theme/jquery.ui.button.css"}
{combine_css path="themes/default/js/ui/theme/jquery.ui.dialog.css"}
{combine_css path="themes/default/js/ui/theme/jquery.ui.theme.css"}

{combine_script id="jquery.ui.dialog" load="footer" require="jquery.ui.button"}
{combine_script id='core.scripts' load='async' path='themes/default/js/scripts.js'}


{combine_script id="jquery.dataTables" load="footer" path="themes/default/js/plugins/jquery.dataTables.js"}

{html_style}{literal}
.ui-accordion-header-icon {display: none}

.dtBar {
	text-align: right;
}

.dtBar DIV {
	display: inline;
	padding-right: 10px;
}

#csTable_filter {
	display: block;
	float: left;
	padding-left: 10px;
}

.dataTables_paginate A {
	padding-left: 3px;
}

#csTable TH {
	cursor: pointer;
}

.sorting_asc:after { content: " \2193" }
.sorting_desc:after { content: " \2191" }
{/literal}{/html_style}

{footer_script}
var SHelper = {

rowFromNode: function(node) {
	return $(node).parents("tr");
},

zoom: function(url){
	jQuery.colorbox( {
		href: url,
		iframe: 1,
		width: "90%", height: "90%"
	});
	return false;
},

del: function(node){
	if (!confirm("Are you sure?")) return false;
	var row = SHelper.rowFromNode(node)
		, item=dataTable.fnGetData(row[0]);
		
	row.fadeTo(500, 0.4);
	(new PwgWS("")).callService(
		"rvac.delCustom", { id: item.id},
		{
			method: "POST",
			onFailure: function(num, text) { row.stop(); row.fadeTo(0,1); alert(num + " " + text); },
			onSuccess: function(result) { 
				dataTable.fnDeleteRow( row[0] );
			}
		}
	);

	return false;
},

add: function() {
	$("#ac_name,#ac_counter,#ac_url").val( "" );
	$("#ac_level").val("0");
	$("#acFormContainer")
		.dialog("option", "buttons", [{ text: "Create", click: function() {
				(new PwgWS("")).callService(
					"rvac.addCustom", { name:$("#ac_name").val(), counter:$("#ac_counter").val(), level:$("#ac_level").val(), url:$("#ac_url").val() },
					{
						method: "POST",
						onFailure: function(num, text) {
							alert(num + " " + text); 
						},
						onSuccess: function(result) {
							dataTable.fnAddData(result);
							$("#ac_name,#ac_counter,#ac_url").val( "" );
						}
					}
				);
			}
		}, { text: "Close", click: function(){ $(this).dialog("close") }} ])
		.dialog("open");
},

edit: function(node) {
	var row = SHelper.rowFromNode(node)
		, item=dataTable.fnGetData(row[0]);

	$("#ac_name").val( item.name );
	$("#ac_counter").val( item.counter );
	$("#ac_level").val( item.level );
	$("#ac_url").val( item.url );

	$("#acFormContainer")
		.dialog("option", "buttons", [{ text: "Modify", click: function() {
				(new PwgWS("")).callService(
					"rvac.modCustom", { id: item.id, name:$("#ac_name").val(), counter:$("#ac_counter").val(), level:$("#ac_level").val(), url:$("#ac_url").val() },
					{
						method: "POST",
						onFailure: function(num, text) {
							alert(num + " " + text); 
						},
						onSuccess: function(result) {
							dataTable.fnUpdate(result, row[0]);
							$("#acFormContainer").dialog("close");
						}
					}
				);
			}
		}, { text: "Close", click: function(){ $(this).dialog("close") }} ])
		.dialog("open");

	return false;
}
}

$().ready( function() {
	var table = $("#csTable");
	dataTable = table.dataTable( {
		sDom : '<"dtBar"filp>rt<"dtBar"ilp>',
		iDisplayLength: 100,
		aaData: [{foreach from=$suggestions item=s name=sloop}
{ldelim}id:{$s.id},name:"{$s.name|@escape:javascript}",counter:{$s.counter},level:{$s.level},url:"{$s.url|@escape:javascript}",U_LINK:"{$s.U_LINK|@escape:javascript}"{rdelim}{if !$smarty.foreach.sloop.last},{/if}
{/foreach}
],
		aoColumnDefs: [ {
				aTargets: ["dtc_Name"],
				mData: function(item) {
					return item.name;
				}
			},{
				aTargets: ["dtc_Weight"],
				bSearchable: false,
				mData: function(item) {
					return item.counter;
				}
			},{
				aTargets: ["dtc_Level"],
				mData: function(item, type) {
					if ("sort"===type)
						return item.level;
					switch (item.level) {
					{foreach $available_permission_levels as $level=>$label}
						case {$level}:
						case "{$level}":
							return "{$label|escape:'javascript'}";
					{/foreach}
					}
					return item.level;
				}
			},{
				aTargets: ["dtc_Url"],
				mData: function(item, type) {
					if ("sort"===type || "filter"==type)
						return item.url ? item.url : item.U_LINK;
					return "<a href=\""+item.U_LINK+"\" onclick=\"return SHelper.zoom(this.href)\">"
						+ (item.url ? item.url : "show" )
						+ "</a>"
				}
			},{
				aTargets: ["dtc_Actions"],
				bSearchable: false,
				bSortable: false,
				mData: function(item) {
					return "<a href=\"\" onclick=\"return SHelper.edit(this)\" class=icon-pencil title=Edit></a>"
						+ " &nbsp; <a href=\"\" onclick=\"return SHelper.del(this)\" class=icon-cancel-circled title=Delete></a>";
				}
			}
		],
		asStripeClasses: ["row1", "row2"],
		bAutoWidth: false,
		aaSorting: []
	}); 
	
	$("#acFormContainer").dialog({
		autoOpen: false,
		modal: true,
		width: "auto"
	});
	$(".openAddNew").click( SHelper.add ).button();
	
	$("tr", table).hover(
		function() {
			$(this).addClass("ui-state-highlight");
		},
		function() {
			$(this).removeClass("ui-state-highlight");
		}
	);
});
{/footer_script}
