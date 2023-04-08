<?php
        $mediaUrl = $this->mediaUrl;
        $media = $this->media;
        $mtime = $this->mtime;
        $aspect_ratios = rex_config::get('cropper', 'aspect_ratios');
        $aspect_ratios = str_replace(',','.', $aspect_ratios);
        $aspect_ratios = preg_split("/\R/", $aspect_ratios);

        $ratios = [];
        foreach($aspect_ratios AS $k=>$v) {
            $r = explode(':', $v);
            if (count($r) == 2) {
                $ratios[] = [
                    'w'=>$r[0],
                    'h'=>$r[1],
                    'r'=>$r[0] / $r[1]
                ];
            }
        }
?>
<div class="cropper_image_wrapper">
    <img id="cropper_image" src="<?= $mediaUrl;?>?buster=<?= $mtime;?>" alt="">
    <div class="docs-buttons">
        <div class="btn-group">
            <button type="button" class="btn btn-primary" data-method="setDragMode" data-option="move" data-toggle="tooltip" data-original-title="Move" data-animation="false">
                <span class="fa fa-arrows"></span>
            </button>
            <button type="button" class="btn btn-primary" data-method="setDragMode" data-option="crop" data-toggle="tooltip" data-original-title="Crop" data-animation="false">
                <span class="fa fa-crop"></span>
            </button>
            <button type="button" class="btn btn-primary" data-method="clear" data-toggle="tooltip" data-original-title="Clear" data-animation="false">
                <span class="fa fa-remove"></span>
            </button>
            <button type="button" class="btn btn-primary" data-method="zoom" data-option="0.1" data-toggle="tooltip" data-original-title="Zoom In" data-animation="false">
                <span class="fa fa-search-plus"></span>
            </button>
            <button type="button" class="btn btn-primary" data-method="zoom" data-option="-0.1" data-toggle="tooltip" data-original-title="Zoom Out" data-animation="false">
                <span class="fa fa-search-minus"></span>
            </button>
            <button type="button" class="btn btn-primary" data-method="rotate" data-option="-45" data-toggle="tooltip" data-original-title="Rotate Left -45" data-animation="false">
                <span class="fa fa-rotate-left"></span>
            </button>
            <button type="button" class="btn btn-primary" data-method="rotate" data-option="45" data-toggle="tooltip" data-original-title="Rotate Right 45" data-animation="false">
                <span class="fa fa-rotate-right"></span>
            </button>
            <button type="button" class="btn btn-primary" data-method="scaleX" data-option="-1" data-toggle="tooltip" data-original-title="Flip Horizontal" data-animation="false">
                <span class="fa fa-arrows-h"></span>
            </button>
            <button type="button" class="btn btn-primary" data-method="scaleY" data-option="-1" data-toggle="tooltip" data-original-title="Flip Vertical" data-animation="false">
                <span class="fa fa-arrows-v"></span>
            </button>
        </div>
    </div>
    <div class="docs-toggles">
        <div class="btn-group d-flex flex-nowrap" data-toggle="buttons">
            <label class="btn btn-primary" data-toggle="none_tooltip" data-animation="false" data-original-title="aspectRatio: <?= $media->getWidth() . ' / ' . $media->getHeight() ;?>">
                <input type="radio" class="sr-only" id="aspectRatio-1" name="aspectRatio" value="<?= str_replace(',', '.', ($media->getWidth() / $media->getHeight()));?>">Original
            </label>

            <?php foreach ($ratios AS $i => $ratio) :?>
            <label class="btn btn-primary" data-toggle="none_tooltip" data-animation="false" data-original-title="aspectRatio: <?= $ratio['w']?> / <?= $ratio['h']?>">
                <input type="radio" class="sr-only" id="aspectRatio<?= $i;?>" name="aspectRatio" value="<?= $ratio['h'];?>"><?= $ratio['w']?>:<?= $ratio['h']?>
            </label>
            <?php endforeach;?>

            <label class="btn btn-primary active free" data-toggle="none_tooltip" data-animation="false" data-original-title="aspectRatio: NaN">
                <input type="radio" class="sr-only" id="aspectRatio-free" name="aspectRatio" value="NaN">Free
            </label>
        </div>
    </div>
</div>

<input type="hidden" id="dataX" name="x">
<input type="hidden" id="dataY" name="y">
<input type="hidden" id="dataWidth" name="width">
<input type="hidden" id="dataHeight" name="height">
<input type="hidden" id="dataRotate" name="rotate">
<input type="hidden" id="dataScaleX" name="scaleX">
<input type="hidden" id="dataScaleY" name="scaleY">