function add_channel(type_id,channel_serial)
{	if (type_id=="#cat")
	{	channel_definition="is_category(" + jQuery('#cat option:selected').val() + ")";
		channel_title='<b>Category Channel</b> ' + jQuery('#cat option:selected').text();
	}
		
	if (type_id=="#tags")
	{	channel_definition=jQuery('#tags').val();
		channel_title='<b>Conditional Channel</b> ' + channel_definition;
	}
	
	if (channel_definition=="")
	{	alert("Enter valid PHP to define a 'Conditional Tags' Channel");
		return;
	}

	jQuery.each(jQuery(".channel_data"), function(){
		if (jQuery(this).find("input:eq(1)").val()==channel_definition)
		{ channel_definition="#" ;return false; }
	});
	if (channel_definition=="#")
	{	alert("Channel already defined");
		return;
	}

	channel_dom=jQuery('#template').clone().removeAttr('id').insertBefore('#new_channel');
	if (channel_serial==undefined) channel_serial=(new Date()).getTime();

	channel_dom.children('input:eq(0)').val(channel_serial);
	channel_dom.children('input:eq(1)').val(channel_definition);
	channel_dom.children('span').html(channel_title);
	channel_dom.css({display: 'block'});
	
	jQuery('#cat').val('');
	jQuery('#tags').val('');
	
	return channel_dom.find('select');
	
}

function add_field(el,value)		
{	if (value==undefined) value="";
	if (jQuery(el).val()=='_') return false;
	channel_id=jQuery(el).parents('div.channel_data').children('input:eq(0)').val();
	new_row='<tr><th>' + jQuery(el).children('option:selected').text() + '</th>';

	if (jQuery(el).val()=='itunes:category')
	{	new_row+="<td>" + jQuery('#cat_menus').html() + "</td>";
		new_row=new_row.replace(/\[\]/g, "[" + channel_id + "]");
		new_row=jQuery(new_row);
		value=value + "||||||"
		categories=value.split("||");
		jQuery(new_row).find("select:eq(0)").val(categories[0]);
		jQuery(new_row).find("select:eq(1)").val(categories[1]);
		jQuery(new_row).find("select:eq(2)").val(categories[2]);
	}
	else if (jQuery(el).val()=='itunes:explicit')
	{	new_row+="<td>" + jQuery('#explicit_menu').html() + "</td>";
		new_row=new_row.replace(/\[\]/g, "[" + channel_id + "]");
		new_row=jQuery(new_row);
		if (value!="") jQuery(new_row).find("select").val(value);
	}
	else if (jQuery(el).val()=='meta:episode_author')
	{	new_row+="<td>" + jQuery('#episode_author_menu').html() + "</td>";
		new_row=new_row.replace(/\[\]/g, "[" + channel_id + "]");
		new_row=jQuery(new_row);
		if (value!="") jQuery(new_row).find("select").val(value);
	}
	else if (jQuery(el).val()=='meta:episode_category')
	{	new_row+="<td>" + jQuery('#episode_category_menu').html() + "</td>";
		new_row=new_row.replace(/\[\]/g, "[" + channel_id + "]");
		new_row=jQuery(new_row);
		if (value!="") jQuery(new_row).find("select").val(value);
	}
	else
	{	new_row+="<td><input name='" + jQuery(el).children('option:selected').val() + "[]' type='text' value='" + value + "'></td></tr>";
		new_row=new_row.replace(/\[\]/g, "[" + channel_id + "]");
	}



	jQuery(el).parents('tr').before(new_row);
		
	jQuery(el).children('option:selected').remove();
	jQuery(el).val('_');
	return false;
}

function show_field_default()
{	if (jQuery(this).val()=="")
	{	jQuery(this).val(jQuery(this).attr("default"));
		jQuery(this).attr("default", "[set]");
		jQuery(this).css({color:'grey'});
	}
}

function hide_field_default()
{	if ( jQuery(this).attr("default")=='[set]' )
	{	jQuery(this).attr("default", jQuery(this).val());
		jQuery(this).css({color:'black'});
		jQuery(this).val("");
	}
}

jQuery(document).ready(function(){
	jQuery("form input[type='text']").each(show_field_default);
	jQuery("form input[type='text']").focus(hide_field_default);
	jQuery("form input[type='text']").blur(show_field_default);
});

function extra_home_fields(el)
{	if (jQuery(el).attr("checked"))
		jQuery("#no_inherit").show();
	else
		jQuery("#no_inherit").hide();
}