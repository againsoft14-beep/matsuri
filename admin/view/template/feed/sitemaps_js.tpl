<script type="text/javascript">
	
$(".<?php echo $extension_id; ?>").on("click", ".top-buttons", function()
{
	if (!$(this).attr("href")) {		
		values = $(this).attr("id").split("-");
		task = values[1];

		if (task == "apply") {
			$(".<?php echo $extension_id; ?> input[name='apply']").attr("value", "1");
		} else if (task == "save") {
			$(".<?php echo $extension_id; ?> input[name='apply']").attr("value", "0");
		}

		$(".<?php echo $extension_id; ?> #form-<?php echo $extension_id; ?>").submit();
	
		return false;
	}
});

<?php foreach ($settings as $tab => $options) { ?>
	<?php foreach ($options as $key => $option) { ?>
		<?php if (($option == 'autocomplete') || ($option == 'multiautocomplete')) { ?>
			<?php $name = $fieldbase.'_'.$key; ?>
			<?php $id = str_replace('_', '-', $name); ?>			
		
			$("input[name='autocomplete_<?php echo $key; ?>']").autocomplete({
				"source": function(request, response) {
					$.ajax({
						url: "index.php?route=<?php echo $folder; ?>/<?php echo $extension; ?>/autocomplete&<?php echo (version_compare(VERSION, '3', '>=') ? 'user_token=' : 'token=').$token; ?>&type=<?php echo $key; ?>&keyword=" +  encodeURIComponent(request),
						dataType: "json",
						success: function(json) {
							<?php if ($option == 'autocomplete') { ?>
								json.unshift({
									id: 0,
									name: "<?php echo $text_none; ?>"});
							<?php } ?>
														
							response($.map(json, function(item) {
								return {
									label: item["name"],
									value: item["id"]}
							}));
						}
					});
				},
				"select": function(item) {
					<?php if ($option == 'autocomplete') { ?>
						$("input[name='autocomplete_<?php echo $key; ?>']").val(item["label"]);
						$("input[name='<?php echo $name; ?>']").val(item["value"]);
					<?php } else { ?>
						$("input[name='autocomplete_<?php echo $key; ?>']").val("");
						$("input[name='autocomplete_<?php echo $key; ?>']").parent().find(".autocomplete-results").find("#<?php echo $id; ?>" + item["value"]).remove();
						$("input[name='autocomplete_<?php echo $key; ?>']").parent().find(".autocomplete-results").append("<div id='<?php echo $id; ?>" + item["value"] + "'><i class='fa fa-minus-circle'></i> " + item["label"] + "<input type='hidden' name='<?php echo $name; ?>[]' value='" + item["value"] + "' /></div>");						
					<?php } ?>
				}
			});
		
			<?php if ($option == 'multiautocomplete') { ?>
				$("input[name='autocomplete_<?php echo $key; ?>']").parent().find(".autocomplete-results").delegate(".fa-minus-circle", "click", function() {
					$(this).parent().remove();
				});
			<?php } ?>				
		<?php } ?>		
	<?php } ?>
<?php } ?>

</script>