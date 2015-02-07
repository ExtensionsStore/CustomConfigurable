/**
 * CustomConfigurable admin options javascript
 * 
 * @category   Aydus
 * @package    Aydus_CustomConfigurable
 * @author     Aydus Consulting <davidt@aydus.com>
 */

function CustomConfigurable($)
{
	var productId;
	var uploadOptionImageUrl;
	var removeOptionImageUrl;
	var formKey;
	
	var displayError = function(message)
	{
		if (typeof console == 'object'){
			console.error(message);
		} else {
			alert(message);
		}
		
	};
	
	var uploadFile = function(optionId, optionTypeId, filename, imageData, $optionImageValue)
	{
		if (typeof uploadOptionImageUrl != 'undefined'){
			
			var data = {
				form_key : formKey,
				product_id : productId,
				option_id : optionId, 
				option_type_id : optionTypeId, 
				filename : filename,
				image_data : imageData
			};
			
			$.post(uploadOptionImageUrl, data, function(res){
				
				if (!res.error){
					
					$optionImageValue.val(res.data);
					
					console.log(res.data);
					
					
				} else {
					
					displayError(res.data);
				}
				
			});	
			
		} else {
			
			displayError('Upload url not set');
		}
	};
	
	var clickFile = function(button)
	{
		var $button = $(button);
		var $td = $button.parents('td');
		var $fileButton = $td.find('.cc-option-file');
		$fileButton.click();
	};
	
	var chooseFile = function(fileButton)
	{
		var $fileButton = $(fileButton);
		var $td = $fileButton.parents('td');
		var fileId = $fileButton.attr('id');
		var matches = fileId.match(/product_option_(\d+)_select_(\d+)_file/);
		
		if (matches.length > 0){
			
			var optionId = matches[1];
			var optionTypeId = matches[2];
			var $optionImage = $td.find('.cc-option-image');
			var $optionImageValue = $td.find('.cc-option-image-value');
			
	     	var file    = fileButton.files[0];
			var reader  = new FileReader();

			reader.onloadend = function (e) {
				var filename = file.name;
				var imageData = reader.result;
				$optionImage.attr('src', imageData);
				uploadFile(optionId, optionTypeId, filename, imageData, $optionImageValue);
			}
			
			if (file) {
			    reader.readAsDataURL(file);
			} else {
				$optionImage.attr('src', '');
				$optionImage.hide();
			}		
			
		} else {
			displayError('Option id not set');
		}

	};
	
	var removeFile = function(button)
	{
		var $button = $(button);
		var $td = $button.parents('td');
		var buttonId = $button.attr('id');
		var matches = buttonId.match(/product_option_(\d+)_select_(\d+)_remove/);
		
		if (matches.length > 0){
			
			var $optionImage = $td.find('.cc-option-image');
			var $optionImageValue = $td.find('.cc-option-image-value');
			var optionId = matches[1];
			var optionTypeId = matches[2];
			var data = {
					form_key : formKey,
					product_id : productId,
					option_id : optionId, 
					option_type_id : optionTypeId
			};
			
			$.post(removeOptionImageUrl, data, function(res){
				
				if (!res.error){
										
					$optionImage.attr('src', '');
					$optionImageValue.val('');
					$optionImage.hide();
					console.log(res.data);
					
				} else {
					
					displayError(res.data);
				}
				
			});				

			
		} else {
			displayError('Option id not set');
		}		
		
	};

	return {
		
		init : function(params)
		{	
			if (params.hasOwnProperty('productId') && params.hasOwnProperty('uploadOptionImageUrl') && params.hasOwnProperty('removeOptionImageUrl')){

				productId = params.productId;
				uploadOptionImageUrl = params.uploadOptionImageUrl;
				removeOptionImageUrl = params.removeOptionImageUrl;

			} else {
				displayError('CustomConfigurable missing init parameters');
			}
			
			var formKeyVal = $('#product_edit_form').find('input[name=form_key]').val();
			
			if (formKeyVal){
				formKey = formKeyVal;
			} else {
				displayError('No form key');
			}
		},
		
		chooseFile : function(fileButton)
		{
			chooseFile(fileButton);
		},
		
		clickFile : function(button)
		{
			clickFile(button);
		},
		
		removeFile : function(button)
		{
			removeFile(button);
		},
		
		showImage : function(img)
		{
			var $img = $(img);
			
			if (img.src != ''){
				$img.show();
			}
		}		
				
	};
	
};

if (!window.jQuery){
	document.write('<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js">\x3C/script><script>jQuery.noConflict();</script>');	
	document.write('<script>var customCase = CustomConfigurable(jQuery);</script>');	
} else {
	var customCase = CustomConfigurable(jQuery);
}
