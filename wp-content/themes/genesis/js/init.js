/* Barts HTML5 Kit v4 | MIT 
 * http://www.rockemgraphics.com
 */
var init = document.getElementById("init"), href = init.getAttribute("src"), dir = href.replace("js/init.js",""),
/*********************************************************************************************
 Start Set up
*********************************************************************************************/
dreamweaverMode = true,
//load local

loadUi = true,
//do you want jQuery UI to Load? true or false

desktopPlugins = [
''
],//list all jQuery plugins here ['plugin','plugin','plugin'] 

jQueryVersion = [
'http://code.jquery.com/jquery-1.8.2.js',
'js/jquery-1.8.2.min.js'
],//CDN and Local version of jQuery ['CDN','Local']

jQueryUiVersion = [
'http://code.jquery.com/ui/1.9.1/jquery-ui.js',
'js/jquery-ui-1.9.1.min.js'
],//CDN and Local version of jQueryUi ['CDN','Local']

jQueryUiVersionCss = [
'http://code.jquery.com/ui/1.9.1/themes/base/jquery-ui.css',
'css/themes/ui/jquery-ui-1.9.1.custom.min.css'
];//CDN and Local version of jQueryUi CSS ['CDN','Local']
/*********************************************************************************************
 End Set up
*********************************************************************************************/
var
loadLoad = desktopPlugins.length,
$local = dir.concat(jQueryVersion[1]),
$ui = dir.concat(jQueryUiVersion[1]),
$uiCss = dir.concat(jQueryUiVersionCss[1]);

if ((dreamweaverMode)&&(loadUi)) {
	Modernizr.load([{
		both: [$local,$ui,$uiCss],
		complete: function(){
			loadPlugins();
		}
	}]);
}else if(dreamweaverMode){
	Modernizr.load([{
		load: $local,
		complete:function(){
			loadPlugins();	
	}
}]);
}else{
if (loadUi) {
	
Modernizr.load([{
	both: [jQueryVersion[0],jQueryUiVersion[0],jQueryUiVersionCss[0]],  
    complete: function () {
		if (typeof jQuery == 'undefined') { 
			Modernizr.load([{
        		both: [$local,$ui,$uiCss],
				complete: function () {
					loadPlugins();
					}	
				}]);
			}else{
				
				loadPlugins();
				}
		}
}]);
}else if(loadUi==false){
Modernizr.load([{
	load: jQueryVersion[0],
    complete: function () {
		if (typeof jQuery == 'undefined') { 
			Modernizr.load([{
        		load: $local,
				complete: function () {
					loadPlugins();
					}	
				}]);
			}else{
				loadPlugins();
				}
		}
}]);	
	
}
}

function loadPlugins() {
	if (loadLoad <=1) {
		afterLoad();	
	}else{
		if (loadLoad==1){
			desktopPlugins = dir.concat(desktopPlugins),
			Modernizr.load([{
				load:desktopPlugins,
				complete: function() {afterLoad();}
			}]);
		}else{
				for(i = 0; i < loadLoad; i++){
					desktopPlugins[i]=dir+desktopPlugins[i];
					}
				Modernizr.load([{
				both:desktopPlugins,
				complete: function() {afterLoad();}
				}]);
			}
	}
}
function afterLoad() {
	Modernizr.load(dir+'js/docReady.js');
	$('#loader').fadeOut(600);
}