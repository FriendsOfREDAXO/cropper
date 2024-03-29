$(document).on('rex:ready', function (e, container) {
    let image_cropper = container.find('#cropper_image');
    if (image_cropper.length) {
        image_cropper_init(image_cropper);

        $("#rex-js-rating-text-jpg-quality").on("input change", function(){
            $("#rex-js-rating-source-jpg-quality").val(this.value);
        });
        $("#rex-js-rating-source-jpg-quality").on("input change", function(){
            $("#rex-js-rating-text-jpg-quality").val(this.value);
            $("#rex-js-rating-text-jpg-quality").trigger("change");
        });
        $("#rex-js-rating-text-png-compression").on("input change", function(){
            $("#rex-js-rating-source-png-compression").val(this.value);
        });
        $("#rex-js-rating-source-png-compression").on("input change", function(){
            $("#rex-js-rating-text-png-compression").val(this.value);
            $("#rex-js-rating-text-png-compression").trigger("change");
        });
        $("#rex-js-rating-text-webp-quality").on("input change", function(){
            $("#rex-js-rating-source-webp-quality").val(this.value);
        });
        $("#rex-js-rating-source-webp-quality").on("input change", function(){
            $("#rex-js-rating-text-webp-quality").val(this.value);
            $("#rex-js-rating-text-webp-quality").trigger("change");
        });


        $("#create_new_image").on("change", function(){
            if ($(this).data('disable') != 1)
                $("#new_file_name").collapse('toggle');
        }).on('click', function(){
            if ($(this).data('disable') == 1)
                return false;
        });
    }
});

function image_cropper_init(element) {

    let $dataX = $('#dataX'),
        $dataY = $('#dataY'),
        $dataHeight = $('#dataHeight'),
        $dataWidth = $('#dataWidth'),
        $dataRotate = $('#dataRotate'),
        $dataScaleX = $('#dataScaleX'),
        $dataScaleY = $('#dataScaleY'),
        options = {
            dragMode: 'move',
            zoomOnWheel: false,
            crop: function (e) {
                $dataX.val(Math.round(e.detail.x));
                $dataY.val(Math.round(e.detail.y));
                $dataHeight.val(Math.round(e.detail.height));
                $dataWidth.val(Math.round(e.detail.width));
                $dataRotate.val(e.detail.rotate);
                $dataScaleX.val(e.detail.scaleX);
                $dataScaleY.val(e.detail.scaleY);
            }
        },
        uploadedImageType = 'image/jpeg';

    // Tooltip
    $('[data-toggle="tooltip"]').tooltip();

    // Cropper
    element.on({
        ready: function (e) {
            console.log(e.type);
        },
        cropstart: function (e) {
            console.log(e.type, e.detail.action);
        },
        cropmove: function (e) {
            console.log(e.type, e.detail.action);
        },
        cropend: function (e) {
            console.log(e.type, e.detail.action);
        },
        crop: function (e) {
            console.log(e.type);
        },
        zoom: function (e) {
            console.log(e.type, e.detail.ratio);
        }
    }).cropper(options);

    // Buttons
    if (!$.isFunction(document.createElement('canvas').getContext)) {
        $('button[data-method="getCroppedCanvas"]').prop('disabled', true);
    }

    if (typeof document.createElement('cropper').style.transition === 'undefined') {
        $('button[data-method="rotate"]').prop('disabled', true);
        $('button[data-method="scale"]').prop('disabled', true);
    }

    // Options
    $('.docs-toggles').on('change', 'input', function () {
        let $this = $(this),
            name = $this.attr('name'),
            type = $this.prop('type'),
            cropBoxData,
            canvasData;

        if (!element.data('cropper')) {
            return;
        }

        if (type === 'checkbox') {
            options[name] = $this.prop('checked');
            cropBoxData = element.cropper('getCropBoxData');
            canvasData = element.cropper('getCanvasData');

            options.ready = function () {
                element.cropper('setCropBoxData', cropBoxData);
                element.cropper('setCanvasData', canvasData);
            };
        } else if (type === 'radio') {
            options[name] = $this.val();
        }

        element.cropper('destroy').cropper(options);
    });


    // Methods
    $('.docs-buttons').on('click', '[data-method]', function () {
        let $this = $(this),
            data = $this.data(),
            cropper = element.data('cropper'),
            cropped,
            $target,
            result;

        if ($this.prop('disabled') || $this.hasClass('disabled')) {
            return;
        }

        if (cropper && data.method) {
            data = $.extend({}, data); // Clone a new one

            if (typeof data.target !== 'undefined') {
                $target = $(data.target);

                if (typeof data.option === 'undefined') {
                    try {
                        data.option = JSON.parse($target.val());
                    } catch (e) {
                        console.log(e.message);
                    }
                }
            }

            cropped = cropper.cropped;

            switch (data.method) {
                case 'rotate':
                    if (cropped && options.viewMode > 0) {
                        element.cropper('clear');
                    }

                    break;

                case 'getCroppedCanvas':
                    if (uploadedImageType === 'image/jpeg') {
                        if (!data.option) {
                            data.option = {};
                        }

                        data.option.fillColor = '#fff';
                    }

                    break;
            }

            result = element.cropper(data.method, data.option, data.secondOption);

            switch (data.method) {
                case 'rotate':
                    if (cropped && options.viewMode > 0) {
                        element.cropper('crop');
                    }

                    break;

                case 'scaleX':
                case 'scaleY':
                    $(this).data('option', -data.option);
                    break;

                case 'getCroppedCanvas':
                    if (result) {
                        // Bootstrap's Modal
                        $('#getCroppedCanvasModal').modal().find('.modal-body').html(result);
                    }

                    break;

            }

            if ($.isPlainObject(result) && $target) {
                try {
                    $target.val(JSON.stringify(result));
                } catch (e) {
                    console.log(e.message);
                }
            }
        }
    });

}