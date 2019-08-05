

var countryData = [];
var countryCode = [];

//for each country, set the code and value
jQuery.each(dataC.countries, function () {

    countryData[this.ccode] = this.country_grant_rating_color;
   
});


(function ($) {

    var countrydata;
    function show(param) {
        
        if (document.getElementById('countrygrantdetails').style.visibility == 'hidden') {
            document.getElementById('countrygrantdetails').style.opacity=1;
            $("#countrygrantdetails").html(param);
            document.getElementById('countrygrantdetails').style.visibility = 'visible';

        }
        
        return false;
    }
    function hide() {
        if (document.getElementById('countrygrantdetails').style.visibility == 'visible') {
            document.getElementById('countrygrantdetails').style.opacity=-0.9;
            document.getElementById('countrygrantdetails').style.visibility = 'hidden';
        }
        
        return false;
    }
    
	 
    jQuery.noConflict();

    jQuery(function () {
      
        var $ = jQuery;
        var map = $('#map1').vectorMap({
            map: 'world_mill_en',
            backgroundColor: "rgba(244, 68, 34, 0)",
            scale: 5,
            series: {
                regions: [{
                        values: countryData, //jQuery(this).find('Drupal.settings.gf_implementers_map_data').eq(0)[1],
               attribute: 'fill',
                    }]           },
            onRegionTipShow: function (e, label, code) {
                //  el.html(el.html() + ' (GDP - ' + dataC.countries[0] + ')');
                 
                countrydata = $.grep(dataC.countries, function (obj, index) {
                    if (obj.ccode == code)
                        return obj.ccode;

                })[0];
                  var text;
                  
                  
                  
                if ((countrydata != undefined)) {
                   text='<div style=" background-color:white;color:#195667;border-radius:3px;height:auto;margin:0px;border:0px;" >'
                           +'<div class="tip2" >'
                           +'<div class="cname1"><strong>' + label.html() + '</strong></div>' +
                            '<br>Average rating : <strong>' + countrydata.country_grant_rating +'</strong>'
                            +'<br> Latest rating : <strong> ' + countrydata.latest_grant_rating + '</strong>';
                     if (countrydata.number_of_active_grants!=0) {
                          text = text +  '<div class="acgtip"> No of active grants : <strong>' + countrydata.number_of_active_grants + '</strong></div><div class="actginfo">'
                
           } 
                            if (countrydata.number_of_hiv_grants!=0) {
                          text = text +  ' HIV  : <strong>' + countrydata.number_of_hiv_grants + '</strong>';
                } 
                    if (countrydata.number_of_tb_grants!=0) {        
                          text = text +  ' <br> TB  : <strong>' + countrydata.number_of_tb_grants + ' </strong>  ' ;
                                }
                        if  (countrydata.number_of_malaria_grants!=0) {
                           text = text + ' <br> Malaria  : <strong>' + countrydata.number_of_malaria_grants + ' </strong> ';
                           }
                          if (countrydata.number_of_hivtb_grants!=0) {
                            text = text  +  '<br>  HIV/TB  : <strong>' + countrydata.number_of_hivtb_grants + ' </strong> ' ;
                           }
                          if (countrydata.number_of_hss_grants!=0) {
                              text = text +  ' <br>  HSS  : <strong>' + countrydata.number_of_hss_grants + ' </strong>  ';
                              }
                             text = text  +' </div</div></div >';
                         }
                if (!(countrydata != undefined)) {
                      
                    
                    label.html( '<div  style="padding:3px;margin:1px;">'+label.html()+' is not a recipient</br> of the Global Fund</div>');
                }
                else {
                    
                            
                    label.html(text
                            );
                }
                //  console.log(countrydata.amount_disbursed);
            },
            onRegionOver: function (e, el, code) {
                hide();
                var countryInfo = $.grep(dataC.countries, function (obj, index) {
                    if (obj.ccode == el)
                        return obj.ccode;

                })[0]; 
   var countryName='';
                   var map = $('#map1').vectorMap('get', 'mapObject');
                   countryName=map.getRegionName(el);
                if (!(countryInfo != undefined)) {
                    var text = '<div><div class="jvcountry" style="font-family: "Open Sans",Arial,Helvetica,sans-serif;font-weight: 600;font-size: 18px"> '
                             +'<img src="/sites/all/themes/goodnex_sub/images/flags/'+el.toLowerCase()+'.png" align="middle"/>'
                     +'<span> '+ countryName + '</span></div>'+
                            '<div class="jvtaa"> </div></div> ' 
                              +countryName+' is not a recipient of the Global Fund </div>  ';
  
                }
                else {//console.log(countryInfo.amount_disbursed);
                    var text = '<div><div class="jvcountry">  <img src="/sites/all/themes/goodnex_sub/images/flags/'+el.toLowerCase()+'.png" align="middle"/><span>' + countryInfo.cname + '</span></div> ' +
                            '<div class="jvtaa"> </div> ' +
                            '<div class="jvtp">Agreement amount to date:<span>' + countryInfo.agreement_amount + ' </span></div> ' +
                            '<div class="jvtp">Disbursed to date:<span>' + countryInfo.amount_disbursed + ' </span></div> ' +
                            '<div class="jvtaa2">  </div><span style=" font-family: Open Sans ,Arial,Helvetica,sans-serif;"> Disbursed to date </span>';
                    if ((countryInfo.total_disbursments_hiv.indexOf("$0.0 m") == -1)) {
                        text = text + '<div class="jvtdtd"> HIV :<strong> ' + countryInfo.total_disbursments_hiv + '</strong> </div> ';
                    }
                    if ((countryInfo.total_disbursments_tb.indexOf("$0.0 m") == -1)) {
                    text = text + '<div class="jvtdtd"> TB :<strong> ' + countryInfo.total_disbursments_tb + ' </strong></div>';
                }
                if ((countryInfo.total_disbursments_malaria.indexOf("$0.0 m")== -1)) {
                    text = text + '<div class="jvtdtd"> Malaria :<strong> ' + countryInfo.total_disbursments_malaria + ' </strong></div> ';
                }
                if ((countryInfo.total_disbursments_hivtb.indexOf("$0.0 m") == -1)) {
                    text = text + '<div class="jvtdtd"> HIV/TB :<strong> ' + countryInfo.total_disbursments_hivtb + ' </strong></div> ';
                }
                if ((countryInfo.total_disbursments_hss.indexOf("$0.0 m") == -1)) {
                   text = text + '<div class="jvtdtd"> HSS :<strong> ' + countryInfo.total_disbursments_hss + ' </strong></div> ';

               }
                    '</div>'
                }
                show(text);
            },
            onRegionOut: function (event, code) {
              hide();

               var text = '<div id="countrygrantdetails" >' +
                        'Hover over a country to view more details about the grant or click to open the country page <br><br><div class="legend2"><div id="pra1"><div style="' +
                        'display: block; height: 12px; border-radius: 3px; width: 12px; background-color: #41A317; float: left;' +
                        'margin-right: 5px;"></div>A1  <b>Exceeds expectations</b></div><div id="pra">' +
                        '<div style="display: block;height: 12px;border-radius: 3px;width: 12px;background-color: #ADA96E;float: left;' +
                        'margin-right: 5px;"></div>A <b>Good performance</b></div><div id="pra2"><div style="display: block;height: 12px;' +
                        'border-radius: 3px;width: 12px;background-color: #A9CF38;float: left;margin-right: 5px;"></div>A2 <b>Meets expectations</b></div><div id="prb1">' +
                        '<div style="display: block;height: 12px;border-radius: 3px;width: 12px;background-color:#DBBD2E;float: left;margin-right: 5px;"></div>B1 <b>Adequate</b></div><div id="prb2">' +
                        '<div style="display: block;height: 12px;border-radius: 3px;width: 12px;background-color:#CE9159;float: left;' +
                        'margin-right:5px; "></div> B2 <b>Inadequate but potential <br> &nbsp;&nbsp;&nbsp;&nbsp;demonstrated</b></div><div id="prc"><div style="display: block;height: 12px;' +
                        'border-radius: 3px;width: 12px;background-color:#CC2222;float: left;margin-right: 5px;"></div>C <b>Unacceptable</b>'
+'<div id="prc"><div style="display: block;height: 12px;' +
                        'border-radius: 3px;width: 12px;background-color:#6B6E73;float: left;margin-right: 5px;"></div>N/A<b> Not available</b></div></div></div>';
             show(text);
            },
            onRegionClick: function (event, code) {
                var country = $.grep(dataC.countries, function (obj, index) {
                    return obj.ccode == code;
                })[0]; 
                window.location.href = "../country/" + country.cid;
            },
            onRegionLabelShow: function (e, el, code) {
                //search through dataC to find the selected country by it's code
                var country = $.grep(dataC.countries, function (obj, index) {
                    return obj.ccode == code;
                })[0]; //snag the first one
                //only if selected country was found in dataC
                if (country != undefined) {
   
                }
            },
        });


    });
     
      $( "#countrygrantdetails" ).stop().animate({ "opacity": 1 },300);
    	 
})(jQuery); 
  
 