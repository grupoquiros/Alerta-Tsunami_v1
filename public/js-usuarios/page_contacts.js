var ContactPage = function () {

    return {
        
    	//Basic Map
        initMap: function () {
			var map;
			$(document).ready(function(){
			  map = new GMaps({
				div: '#map',
				lat: 37.280542,
				lng: -5.917633
			  });
			  
			  var marker = map.addMarker({
				lat: 37.280542,
				lng: -5.917633,
	            title: 'TSigno'
		       });
			});
        },

        //Panorama Map
        initPanorama: function () {
		    var panorama;
		    $(document).ready(function(){
		      panorama = GMaps.createPanorama({
		        el: '#panorama',
		        lat : 37.280542,
		        lng : -5.917633
		      });
		    });
		}        

    };
}();