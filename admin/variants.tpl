<div class="titrePage">
  <h2>RV Autocomplete {$TABSHEET_TITLE}</h2>
</div>

<div id="acFormContainer" style="text-align:left">
<form>
<table>
<tr>
<td>
	<label for=ac_in>{'When any of the following words is entered'|@translate}:</label><br>
	<textarea id="ac_in" style="width:100%" rows="5"></textarea>
</td>
</tr>

<tr>
<td>
	<select id="ac_type"> 
		<option value="r">{'Ignore it and'|translate}</option>
		<option value="a">{'Search it and'|translate}</option>
	</select>

</td>
</tr>

<tr>
<td>
	<label for=ac_out>{'Search also for any of the following'|translate}:</label><br>
	<textarea id="ac_out" style="width:100%" rows="3"></textarea>
</td>
</tr>

<tr>
<td>
	<label for=ac_comment>{'Comment'|@translate}:</label><br>
	<input id="ac_comment" style="width:100%">
</td>
</tr>

</table>
</form>
</div>


<p><a class="openAddNew">{'Create new variants'|@translate}</a></p>
<table id="csTable" class="table2">
<thead>
<tr class="throw">
<th class="dtc_In">{'In'|@translate}</th>
<th class="dtc_Type">{'Type'|@translate}</th>
<th class="dtc_Out">{'Out'|@translate}</th>
<th class="dtc_Comment">{'Comment'|@translate}</th>
<th class="dtc_Actions">{'Actions'|@translate}</th>
</tr>
</thead>
<tbody>
</tbody>
</table>

<p><a class="openAddNew">{'Create new variants'|@translate}</a></p>

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

del: function(node){
	if (!confirm("Are you sure?")) return false;
	var row = SHelper.rowFromNode(node)
		, item=dataTable.fnGetData(row[0]);
		
	row.fadeTo(500, 0.4);
	(new PwgWS("")).callService(
		"rvac.delVariant", { key: item.key},
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
	$("#ac_in,#ac_out,#ac_comment").val( "" );
	$("#ac_type").val("a");

	$("#acFormContainer")
		.dialog("option", "buttons", [{ text: "Create", click: function() {
				(new PwgWS("")).callService(
					"rvac.addVariant", { in:$("#ac_in").val(), out:$("#ac_out").val(), type:$("#ac_type").val(), comment:$("#ac_comment").val() },
					{
						method: "POST",
						onFailure: function(num, text) {
							alert(num + " " + text); 
						},
						onSuccess: function(result) {
							dataTable.fnAddData(result.rule);
							$("#ac_in,#ac_out").val( "" );
							if (result.messages.length)
								alert(result.messages);
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

	$("#ac_in").val( item.in.join("\n") );
	$("#ac_out").val( item.out.join("\n") );
	$("#ac_type").val( item.type );
	$("#ac_comment").val( item.comment );

	$("#acFormContainer")
		.dialog("option", "buttons", [{ text: "Modify", click: function() {
				(new PwgWS("")).callService(
					"rvac.modVariant", { in:$("#ac_in").val(), out:$("#ac_out").val(), type:$("#ac_type").val(), comment:$("#ac_comment").val(), key:item.key },
					{
						method: "POST",
						onFailure: function(num, text) {
							alert(num + " " + text); 
						},
						onSuccess: function(result) {
							dataTable.fnUpdate(result.rule, row[0]);
							if (result.messages.length)
								alert(result.messages);
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
		aaData: {$variants|json_encode},
		aoColumnDefs: [ {
				aTargets: ["dtc_In"],
				mData: function(item) {
					return item.in.join(", ");
				}
			},{
				aTargets: ["dtc_Type"],
				bSearchable: false,
				mData: function(item) {
					return item.type;
				}
			},{
				aTargets: ["dtc_Out"],
				mData: function(item, type) {
					return item.out.join(", ");
				}
			},{
				aTargets: ["dtc_Comment"],
				mData: function(item, type) {
					return item.comment ? item.comment : "";
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
