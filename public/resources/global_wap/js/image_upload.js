function detectVerticalSquash(img) {
    var iw = img.naturalWidth, ih = img.naturalHeight;
    var canvas = document.createElement('canvas');
    canvas.width = 1;
    canvas.height = ih;
    var ctx = canvas.getContext('2d');
    ctx.drawImage(img, 0, 0);
    var data = ctx.getImageData(0, 0, 1, ih).data;
    // search image edge pixel position in case it is squashed vertically.
    var sy = 0;
    var ey = ih;
    var py = ih;
    while (py > sy) {
        var alpha = data[(py - 1) * 4 + 3];
        if (alpha === 0) {
            ey = py;
        } else {
            sy = py;
        }
        py = (ey + sy) >> 1;
    }
    var ratio = (py / ih);
    return (ratio===0)?1:ratio;
}

/**
 * A replacement for context.drawImage
 * (args are for source and destination).
 */
function drawImageIOSFix(ctx, img, sx, sy, sw, sh, dx, dy, dw, dh) {
    var vertSquashRatio = detectVerticalSquash(img);
    ctx.drawImage(img, sx * vertSquashRatio, sy * vertSquashRatio, 
                       sw * vertSquashRatio, sh * vertSquashRatio, 
                       dx, dy, dw, dh );
}

function draw(ctx,img,cvs,orientation,size){
	var imgX=0,imgY=0,imgH=img.height,imgW=img.width;
	var width = size;
	var height = size;
	if(orientation != 1){
		switch(orientation) {
			case 6:
				imgW = img.height;
				imgH = img.width;
				var angel = 0.5*Math.PI;  
				break;
			case 3: 
				var angel = Math.PI;  
				break;
			case 8: 
				imgW = img.height;
				imgH = img.width;
				var angel = -0.5*Math.PI;  
				break;
			default:
				break;
		}
	}
	if(imgH>imgW){
		if(imgH>size){
			width = size/imgH*imgW;
		}else{
			width = imgW;
			height = imgH;
		}
	}else{
		if(imgW>size){
			height = size/imgW*imgH;
		}else{
			width = imgW;
			height = imgH;
		}
	}
	cvs.height = height;
	cvs.width = width;
	if(orientation != 1){
		ctx.translate(cvs.width/2,cvs.height/2);  
		ctx.rotate(angel);
		imgX = 0 - cvs.width/2;  
		imgY = 0 - cvs.height/2;
	}
	if(orientation ==6 || orientation == 8){
		drawImageIOSFix(ctx,img,0,0,img.width,img.height,imgY,imgX,cvs.height,cvs.width);
	}else{
		drawImageIOSFix(ctx,img,0,0,img.width,img.height,imgX,imgY,cvs.width,cvs.height);
	}
}