var RBC = RBC || {};  

RBC = {
	admin:{
		nav:{},
		program:{}
	},
	home:{
		swapbox:{}
	},
	utility:{}
};            

RBC.utility = (function(){
	return{
		reloadCSS : function(){
			var href = $('#mastercss').attr('href').split("?")[0];
			$('#mastercss').attr('href', href+'?reload='+ new Date().getTime());
		}
	} 
}());

RBC.admin.program =(function (){
	var detail_panel = {
		preview : function(program_id, display_element){
			var pid = program_id,
				view = display_element || $(".program_right");
				view.empty().html("<div class='icon-loading-middle'></div>");
				view.load('/admin/programdetail',{id:pid, state:"preview"});
		},
		
		edit : function(program_id, edit_element){
			var pid = program_id,
				edit = edit_element || $(".program_left");;
				edit.empty().html("<div class='icon-loading-middle'></div>");
				$('form#form-add-measurement').remove();
				edit.load('/admin/programdetail',{id:pid, state:"edit"}, function(){
					addItem($('a.add-item'), pid)
					sortable($("#admin-program-detail-sortable"), pid);
					remove($("#admin-program-detail-sortable"),pid);
					editable(pid);
				});
		},
	},  
	
	addItem = function(add_btn, pid, add_form){ 
		add_btn.button({ icons:{primary:'ui-icon-plus'}});
		var add_form = add_form || $('form#form-add-measurement') ;
		add_btn.bind({
			click: function(){
				add_form.dialog({
					height: 200,
					width: 350, 
					modal:true,
					buttons: {
						Add : function(){
							var mname = $('#mname').val(),
								munit = $('#munit').val();
							$.post("/admin/programaddmeasurement", {
								pid:pid,
								mname:mname,
								munit:munit
							},function(response){
								console.log(response);
								detail_panel.preview(pid);
								detail_panel.edit(pid);
							})
							$(this).dialog("close");
						},
						Cancel : function(){
							$(this).dialog("close");
						}
					},
					close: function(){
						$('#mname,#munit').val("");
					}
				})
				return false;
			}
		})
	},
	
	editable = function(id){
		$('div.program-name').editable('/admin/programcontent',{
			submitdata: {id: id, item: 'name'},
			indicator : "<img src='/images/icon-loading.gif'>",
			type	  : "text",
			tooltip   : "Click to edit...",
			submit  : 'Update',
			style  : "inherit",
			callback : function(){
				detail_panel.preview(id, $(".program_right"))
			}
		});
		
		$('div.program-description').editable('/admin/programcontent',{
			submitdata: {id: id, item: 'description'},
			indicator : "<img src='/images/icon-loading.gif'>",
			type	  : "textarea",
			tooltip   : "Click to edit...",
			submit  : 'Update',
			style  : "inherit",
			callback : function(){
				detail_panel.preview(id, $(".program_right"))
			}
		});

		$('.item-name span, .item-unit span').editable('/admin/programitem',{
			submitdata: function(){
				return{
					pid: id,
					id: $(this).parents('li').attr('title'),
					item_type: $(this).parent('div').attr('class')
				}
			},
			indicator : "<img src='/images/icon-loading.gif'>",
			width	  : "100px",
			type	  : "text",
			tooltip   : "Click to edit...",
			style     : "inherit",
			callback : function(){
				detail_panel.preview(id, $(".program_right"))
			}
		});     
	},
	
	sortable = function(ul_ele, pid){
		var start_pos,
			end_pos;
			
		ul_ele.sortable({
			revert: true,
			start: function(event,ui){
				start_pos = ui.item.attr('title');
			},
			update: function(event,ui){ 
			   	ui.item.parent().children().each(function(index){
					$(this).attr('title', index);
				});                              
				end_pos = ui.item.attr('title');
				$.post('/admin/programsort',
					{pid: pid, start_pos:start_pos, end_pos:end_pos},
					function(){
						detail_panel.preview(pid);
					}
				)
			}
		});
	},
	
	remove = function(ul_ele, program_id){
		ul_ele.children('li').mouseenter(function(){
			var pid = program_id,
				id = $(this).attr('title'),
				icon = $('<span class="icon-delete"></span>'),
				width = 266;
			icon.bind({
				click:function(){
					if(icon.parents('ul').children().length==1){
						$('<p class="dialog">A program must have at least one measurment. Please delete the program instead if you do not want the progarm anymore.</p>').dialog({
							show: "highlight",
							hide: "fade",
							height:150
						});
						return;
					}
					$.post("/admin/programdeleteitem",{
						id:id,
						pid:pid
					},function(response){
						var ul_ele = icon.parents('ul');
						icon.parents('li').remove();
						ul_ele.children().each(function(index){
							$(this).attr('title', index);
						});
						detail_panel.preview(pid);
					});
				}
			});
			$(this).animate({
				'width': width,
				'background-position': '90% 50%'
			},100);	
			$(this).children('.clear').before(icon);
		}).mouseleave(function(){
			var width = 250;
			$('.icon-delete').remove();
			$(this).animate({
				'width': width,
				'background-position': '97% 50%'
				},100);
		});                  
		   
	},   
	
	paypal=function(id){
		$.get('/admin/programpaypal',{id:id, req:'preview'},function(paypal_form){
			$(paypal_form).first().dialog({
				height:500,
				width:450,
				modal:true,
				buttons: {
					Update : function(){
						var paypal = $(this).find('textarea[name=paypal_code]').val().trim(),
							preview_ele = $(this).find('div.paypal-preview');
						$.post('/admin/programpaypal', {id:id, req:'update', paypal:paypal},function(response){ 
							console.log(response);
							if(response == 1){
								$.jGrowl("Paypal button saved.",{animateOpen:{opacity: 'show'}});
								preview_ele.empty().html("<div class='icon-loading-middle'></div>");
								$.get('/admin/programpaypal', {id:id,req:'preview'},function(paypal_update){
									var paypal_btn = $(paypal_update).find('div.paypal-preview');
									preview_ele.html(paypal_btn);
								});					
							}else{
								$.jGrowl("An error occured. Please contact admainistrator.")
							};
						})
					},
					Close : function(){
						$(this).dialog("close");
					} 
				},
				close: function(){
					$(this).dialog("destroy");
					$('#form-admin-paypal').remove();
				}

			});
		})
	};
	
	return {
		displayEdit: function(id, edit_ele){
			detail_panel.edit(id, edit_ele, editable); 
		},
		displayView: detail_panel.preview,
		displayAll : function(program_id, edit_ele, view_ele){
			detail_panel.preview(program_id, view_ele);
			detail_panel.edit(program_id, edit_ele);
		},
		paypal:paypal
	};
}());

RBC.admin.nav = {
	run: function(buttonset_ele, button_ele){
		buttonset_ele.buttonset();
		button_ele.button();
	}
}  

RBC.home.swapbox = {
	run: function(swapbox_ele,control_nav){
		var section_height = swapbox_ele.children('section').first().outerHeight(); 
		control_nav.find('li').bind({
			click: function(){
				var postion = Number($(this).attr('class')) + 1,
				 	swapbox_top_pos = (-1) * postion * section_height;
				
				control_nav.find('.active').removeClass('active');

				if ($(this).attr('class')=='back'){
					swapbox_ele.animate({
						'top': 0
					},1000);
					$(this).hide();
					return false
				}   
				
				$(this).children('a').addClass('active');
				swapbox_ele.animate({
					'top': Number(swapbox_top_pos)
				},1000);
				
				control_nav.find('.back').show();
				return false;
			}
		})
	}
};



var c = RBC.utility.reloadCSS;

$(document).ready(function() {  
		
	$(window).focus(function(){
		//RBC.utility.reloadCSS();
		//location.reload(true);
	}); 
	
	if ($('#index-swapbox').length>0){
		RBC.home.swapbox.run($('#index-swapbox'),$('nav.swapbox'));
	}
   
	if ($('nav#admin').length>0){ 
		RBC.admin.nav.run($('nav#admin'),$('nav#admin a'));
	}  	
	
	if ($('#admin-paypal-submit').length>0){
		$('#admin-paypal-submit').button().bind({
			click: function(){
				var paypal_code = $("textarea[name$=paypal_code]").val().trim(),
				 	paypal_pid  = $("input[name$=pid]").val();
				
				$.get(
					"/admin/paypalbtn/",
					{paypal_btn:paypal_code, pid:paypal_pid},
					function(response){
						if (response){
							setTimeout(function(){
								 window.location = "/admin/program/paypal/"+paypal_pid;
							}, 2000);
							$.jGrowl("Paypal button saved. Refreshing...",{
								animateOpen: {
									opacity: 'show'
								}
							});
						} else {
							$.jGrowl("An error has happen, please contact administrator.",{
								animateOpen: {
									opacity: 'show'
								}
							});
						};
					}
				);
			}
		});
	} 
	

	
	
	if ($('#progress-header').length>0) {
		(function userProgress() {
			$.get(
				"/progress/userprogress",
				{},
				function(response){
					var data = $.parseJSON(response),
					    program = [];
				    for (var i = 0, max = data.length; i < max; i++){
						if (data[i].measure === null){							
							program[i] = {
								"pname" : data[i].pname,
								"measures" : null
							};
						} else {
							var measures = data[i].measure.replace(/(\r\n|\n|\r)/gm,"").split(";"),
							 	measure = [];
							 
							if (/^|\s/.test(measures[measures.length-1])){
								measures.splice(-1,1);
							}
							for (var j = 0, l = measures.length; j < l; j++){
								measure.push({
									"mname" : measures[j].split(":")[0],
									"before" : measures[j].split(":")[1].split(",")[0],
									"after" : measures[j].split(":")[1].split(",")[1],
									"unit" : measures[j].split(":")[2],
								});    
							};   
							
							program[i] = {
								"pname" : data[i].pname,
								"measures" : measure
							};  
						}
				    }
					render_progress(program);
					meterHeight();
				}
			);
		}());
		
		function render_progress(program){
			var output, output_before, output_after;
			for (var i = 0, max = program.length; i < max; i++){
				output = '<div class="grid_8 alpha omega progress-meter-wrapper">'; 
				output += '<span>'+program[i].pname+'</span><div class="clear"></div>';
				
				output_before = '<div class="prefix_1 grid_3 alpha progress-meter-before"><div class="progress-meter-top"></div><div class="progress-meter-middle"><div><ul>';
				
				output_after = '<div class="grid_4 alpha progress-meter-after"><div class="progress-meter-top"></div><div class="progress-meter-middle"><div><ul>'
				
				if (program[i].measures === null){
					output_before += '<li><span>Welcome to '+program[i].pname+' ! <br><br>Your measurements will be updated shortly after the initial measurement.<br> <br>The renewal progress starts from here!</span></li>' 
					
				} else {;
					for (var j = 0, l = program[i].measures.length; j < l; j++){
						output_before += '<li><div class="measurement-wrapper"><div class="m-name">'+program[i].measures[j].mname+'</div><div class="m-value">'+program[i].measures[j].before+'</div><div class="m-unit">'+program[i].measures[j].unit+'</div></div><div class="clear"></div></li><hr>';
						output_after += '<li><div class="measurement-wrapper"><div class="m-name">'+program[i].measures[j].mname+'</div><div class="m-value">'+program[i].measures[j].after+'</div><div class="m-unit">'+program[i].measures[j].unit+'</div></div><div class="clear"></div></li><hr>';
					};
				}     
				
				output_before += '</ul></div><img src="/images/bg-progress-lb-middle.png" /></div><div class="progress-meter-bottom"></div></div>'; 
				output_after  += '</ul></div><img src="/images/bg-progress-lb-middle.png" /></div><div class="progress-meter-bottom"></div></div>';
				output += output_before;
				output += output_after;
				output += '</div>';
				
				$("#progress-meter").append($(output));				
			};
		};
		
		function meterHeight(){
			$(".progress-meter-wrapper").each(function(){
				var text_height = $(this).find(".progress-meter-middle div").height();
				$(this).find(".progress-meter-middle").height(text_height);
			})
		}; 
		
		function chgpwd(){
			$( "#dialog:ui-dialog" ).dialog( "destroy" );

			var old_pwd = $("#old-pwd"),
			 	new_pwd = $("#new-pwd"),
				confirm_pwd = $("#confirm-pwd"),
				allFields = $([]).add(old_pwd).add(new_pwd).add(confirm_pwd),
				tips = $(".validateTips");

			function updateTips(t) {
				tips
				.text(t)
				.addClass("ui-state-highlight");
				setTimeout(function() {
					tips.removeClass( "ui-state-highlight", 1500 );
				}, 500 );
			}
            
			function checkOldPwd(pwd){
				$.get(
					"/progress/checkpwd",
					{old_pwd:pwd},
					function(response){
						console.log(response)
					} 
				);
			}
			
			function checkLength( o, n, min, max ) {
				if ( o.val().length > max || o.val().length < min ) {
					o.addClass( "ui-state-error" );
					updateTips( "Length of " + n + " must be between " +
					min + " and " + max + "." );
					return false;
				} else {
					return true;
				}
			}

			function checkRegexp( o, regexp, n ) {
				if ( !( regexp.test( o.val() ) ) ) {
					o.addClass( "ui-state-error" );
					updateTips( n );
					return false;
				} else {
					return true;
				}
			}

			$( "#dialog-form" ).dialog({
				autoOpen: false,
				height: 180,
				width: 350,
				modal: true,
				buttons: {
					"Update Password": function() {
						var bValid = true;
						//allFields.removeClass( "ui-state-error" );
						//bValid = bValid && checkLength( email, "email", 6, 80 );
						bValid = bValid && checkOldPwd("sdfsd");

						//bValid = bValid && checkRegexp( name, /^[a-z]([0-9a-z_])+$/i, "Username may consist of a-z, 0-9, underscores, begin with a letter." );
						// From jquery.validate.js (by joern), contributed by Scott Gonzalez: http://projects.scottsplayground.com/email_address_validation/
						//bValid = bValid && checkRegexp( email, /^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i, "eg. ui@jquery.com" );
						//bValid = bValid && checkRegexp( password, /^([0-9a-zA-Z])+$/, "Password field only allow : a-z 0-9" );

						if ( bValid ) {
						   /*
						    $( "#users tbody" ).append( "<tr>" +
						   							"<td>" + name.val() + "</td>" + 
						   							"<td>" + email.val() + "</td>" + 
						   							"<td>" + password.val() + "</td>" +
						   							"</tr>" ); 
						   							$( this ).dialog( "close" );  */
						   console.log('yes');
						}
					},
					Cancel: function() {
						$( this ).dialog( "close" );
					}
				},
				close: function() {
					allFields.val( "" ).removeClass( "ui-state-error" );
				}
			});

			$( "#change-pwd" )
			.button()
			.click(function() {
				$( "#dialog-form" ).dialog( "open" );
			});
		}
		chgpwd();
	}
	
	if ($('#admin-program-list').length>0){ 	
		$('input:checkbox').checkbox().bind({
			click:function(){
				var pid = $(this).attr('class').split(' ')[0],
				    p_status; 
				if ($(this).attr('checked') == "checked"){
					p_status = 0;
				} else {
					p_status = 1;
				};     
				
				$.get(
					"/admin/programactive",
					{state:"update", p_status:p_status, pid:pid},
					function(response){
						if(response == 1){
							$.jGrowl("Program status saved.",{
								animateOpen: {
									opacity: 'show'
								}
							});
						} else {
							$.jGrowl("An error has happen, please contact administrator.",{
								animateOpen: {
									opacity: 'show'
								}
							});
						};
					}
				)
			}
		});
		 
		
		$('a.program-detail').bind({
			click : function(){
				id = $(this).attr('class').split(" ")[0];
				RBC.admin.program.displayAll(id, $(".program_left"),  $(".program_right"));
				return false;
			}
		});
		
		$('a.program-paypal').bind({
			click : function(){
				$('#form-admin-paypal').dialog( "destroy" );
				id = $(this).attr('class').split(" ")[0];
				RBC.admin.program.paypal(id);
				return false;
			}
		});
		
	};  
	
	if ($('table#admin-client').length>0){
		$('#admin-client-detail > div').hide();
		
		$('table#admin-client td').hover(
			function(){$(this).parent('tr').css('background-color','#eee')},
			function(){$(this).parent('tr').css('background-color','#fff')} 
		);
		
		$('table#admin-client tr').bind({
			click: function(){
				var uid = $(this).attr('class');
				$('#admin-client-detail > div').hide();
				$('#admin-client-detail > div[class='+uid+']').show().parent().css({
					'padding-top': $(window).scrollTop()
				})
				.find('.fname, .lname')
				.editable("/admin/userprofile", {
					submitdata: function(){
                    	return {
							uid :$(this).closest('div').attr('class'),
							item:$(this).attr('class').split(" ")[0]
						}
					}, 
					indicator : "<img src='/images/icon-loading.gif'>",
					tooltip   : "Click to edit...", 
					type	  : "text",
					submit  : 'Update',
					style  : "inherit"
				});
				$('.rst_pwd').bind({
					click: function(){
						var p_uid = $(this).parent().attr('class');
						var p_name = $(this).siblings('p').find('.fname').text()
						$.get(
							"/admin/rstpwd",
							{uid:p_uid},
							function(response){
								if(response == 1){
									$.jGrowl( p_name+"'s password has reseted to 123456.",{
										animateOpen: {
											opacity: 'show'
										}
									});
								} else {
									$.jGrowl("An error has happen, please contact administrator.",{
										animateOpen: {
											opacity: 'show'
										}
									});
								};
							}
						);
					}
				});
			}
		}); 
	} 
	
	
	if ($('#enrollment').length>0) {
	    $.getJSON('/admin/usersdirectory', function(data) {
			var users = [];
			users = $.map(data, function(el,id){
				return {
					"id": id,
					"value": el
				};
			});   		
			usersdirectory(users);
		});

		function usersdirectory(data){
			$('#add-user-to-program').autocomplete({
				source: data,	
				select: function( event, ui ) {
					var pid = $('#program-tb tr.selected').attr('class').split(' ')[0];
					var pname = $('#program-tb tr.selected').text();
					var uid = ui.item.id;
					var uname = ui.item.value;
					$(this).val('');
					$.get(
						"/admin/programusers",
						{pid:pid,uid:uid},
						function(data){
							if (data===0){
								$('<p>The user you selected is already enrolled in '+pname+'</p>').dialog({
									show: "highlight",
									hide: "fade"
								});
							} else if (data==1){
								var tr = $('<tr>').addClass(uid).append('<td>'+uname).appendTo('#user-tb');
								rmUserFunc($(tr).find($("td")));
								progUserDetailFunc($(tr).find($("td")));
							}
						}
					);	         
					return false;
				}
			}); 
		}

		$('#program-tb tr[class!=title]').each(function(i,el){
			$(el).bind({
				click: function(){
					$.get(
						"/admin/programusers", 
						{pid:$(el).attr('class').split(' ')[0]},
						function(data){ 
							$('#user-tb tr[class!=title]').empty(); 
							$.each(data, function(id,user){
								$('<tr>').addClass(user.id).append('<td>'+user.fname+' '+user.lname).appendTo('#user-tb');
							});
							$('#user-tb tr[class!=title] td').each(function(i,el){
								rmUserFunc(el);
								progUserDetailFunc(el);
							})
							$(el).siblings().css({"background-color":"#fff", "color":"#000"}).removeClass('selected');
							$(el).animate({
								"background-color":"#999",
								"color":"#fff"
							})
							.addClass('selected');
						}
					);
					$('#add-user-to-program').removeAttr('readonly');
					$("#measures_data").empty();
				}
			})
		});  


		$('#add-user-to-program').click(function(){
			if($('#add-user-to-program').attr('readonly') == 'readonly'){
				$('<p>Please select a program first</p>').dialog({
					height:100,
					show: "highlight",
					hide: "fade"
				});
			};
		});
		
		function rmUserFunc(td){
			$(td).bind({
				mouseenter: function(){
					var del = $("<img class='icon-cross' style='padding-top:5px;float:right; display:none;' height='16px' width='16px' src='/images/icon-delete.png'>");
					$(td).append(del);
					del.fadeIn(1000);
					del.click(function(){
						var rid = del.closest("tr").attr('class').split(' ')[0]; 
						var pid = $('#program-tb tr.selected').attr('class').split(' ')[0];
						$.get(
							"/admin/programusers",
							{pid:pid,rid:rid},
							function(){
                             	$(td).unbind('click').closest("tr").remove();
								$("#measures_data").empty();
							}
						);
					});
				},
				mouseleave: function(){
					$('#user-tb .icon-cross').remove();
				} 
			})
		}
		
		function progUserDetailFunc(td){
			$(td).bind({
				click: function(){
					var mid = $(td).closest("tr").attr('class').split(' ')[0]; 
					var pid = $('#program-tb tr.selected').attr('class').split(' ')[0];
					
					$(td).closest("tr").siblings().css({"background-color":"#fff", "color":"#000"}).removeClass('selected');
					$(td).closest("tr").animate({
						"background-color":"#999",
						"color":"#fff"
					})
					.addClass('selected');

					$.get(
						"/admin/programusers",
						{pid:pid, mid:mid},
						function(response){
							var data = $.parseJSON(response.replace(/[\[\]]/g,""));
							if (data == null) return false;
							if (data.u_measure == null){
								// user measurement do not exist, load from program default measurements
                            	$("#measures_data").empty().append("Measurements:")

								$.get(
									"/admin/programusers",
									{pidm:pid},
									function(response){
										var data = $.parseJSON(response.replace(/[\[\]]/g,"")); 
										var u_measures = data.p_measure.replace(/(\r\n|\n|\r)/gm,""); 
										var measures = u_measures.split(";");
										var measures_data = [];
									   
										var size = measures.length;                                
		                                for (m=0;m<size;m++){
			 								if (measures[m]===""){
												measures.splice(m,1);
											}
										}
										
										for (i=0; i < size; i++){                   
											measures_data[i] = {
												name: measures[i].split(":")[0],
												unit : measures[i].split(":")[1]
											}
										}
										
										render_p(measures_data);
									}
								) 
                               
								
							} else {
								// user measurement exist
								var u_measures = data.u_measure.replace(/(\r\n|\n|\r)/gm,"");
								var measures = u_measures.split(";");
								var measures_data = [];
                                
								var size = measures.length;                                
                                for (m=0;m<size;m++){
	 								if (measures[m]===""){
										measures.splice(m,1);
									}
								}   
								
								for (i=0; i < measures.length; i++){                   
									measures_data[i] = {
										name: measures[i].split(":")[0],
										before: measures[i].split(":")[1].split(",")[0].trim(),
										after : measures[i].split(":")[1].split(",")[1].trim(),
										unit : measures[i].split(":")[2]
									}
								}

								render(measures_data);
							}					
						}
					);

					function render(measures){
						var before_v = "";
						var after_v = "";
						var submit_btn = "";
						
						for (i=0; i < measures.length; i+=1){
							item_before = '<li><label>'+ measures[i].name +'</label> <input type="text" value="'+ measures[i].before +'" size="5"/> &nbsp <span>'+ measures[i].unit+'</span></li>';
							before_v += item_before;
							item_after =  '<li><label>'+ measures[i].name +'</label> <input type="text" value="'+ measures[i].after +'" size="5"/> &nbsp <span>'+ measures[i].unit+' </span></li>'
							after_v += item_after;
						}
						
					   	before_v = "Measurements:<br/> Before:<br/> <ul>"+before_v+"</ul>";
						after_v = "After:<br/> <ul>"+after_v+"</ul><br/>";
						submit_btn =  $("<button>").attr('id','save_measurement')
						.click(function(){
							save_measures();
						})
						.button({
							label: "Update Measurements", 
							icons: {primary:'ui-icon-check'}
						});
						
						$("#measures_data").empty().append(before_v).append(after_v).append(submit_btn);
					}
					
					function render_p(measures){
						var before_v = "";
						var after_v = "";
						var submit_btn = "";
						
						for (i=0; i < measures.length; i+=1){
							item_before = '<li><label>'+ measures[i].name +'</label> <input type="text" value="" size="5"/> &nbsp <span>'+ measures[i].unit+'</span></li>';
							before_v += item_before;
							item_after =  '<li><label>'+ measures[i].name +'</label> <input type="text" value="" size="5"/> &nbsp <span>'+ measures[i].unit+'</span></li>'
							after_v += item_after;
						}
						
					   	before_v = "Measurements:<br/> Before:<br/> <ul>"+before_v+"</ul>";
						after_v = "After:<br/> <ul>"+after_v+"</ul><br/>";
						submit_btn =  $("<button>").attr('id','save_measurement')
						.click(function(){
							save_measures();
						})
						.button({
							label: "Create Profile", 
							icons: {primary:'ui-icon-check'}
						});
						 
						$("#measures_data").empty().append(before_v).append(after_v).append(submit_btn);
					} 
					
					function save_measures(){
						var pid = $('#program-tb tr.selected').attr('class').split(' ')[0];
						var uid = $('#user-tb tr.selected').attr('class').split(' ')[0] 
						var data = new Array($('#measures_data ul').length-1);    
						
						$('#measures_data ul').each(function(i, el_ul){
							data[i] = new Array($(this).length-1);
							$(this).find('li').each(function(j, el_li){
								data[i][j]= $(this);
							});
						});
						
						var measures= [];
						for (j=0;j<data[0].length;j+=1){
							var label = data[0][j].find('label').text();
							var bvalue = data[0][j].find('input').attr('value') || "N/A";
							var avalue = data[1][j].find('input').attr('value') || "N/A";
							var unit = data[1][j].find('span').text().trim();
							 

							measures.push(label+":"+bvalue+","+avalue+":"+unit);
						}
                        
						var measures_output = measures.join(";");
						$.get(
							"/admin/programusers",
							{m_output:measures_output, pid:pid, uid:uid},
							function(response){
								 
								$.jGrowl("Measurments Saved",{
										animateOpen: {
									        opacity: 'show'
									    }
								 });
							}
						)
						
					}
				}
			})
		}
	} 
	
	if ($('#admin-contents').length>0) {
		$('p#introduction, p#about').editable("/admin/updatecontent", {
			submitdata: function(){
            	return {
					item: $(this).attr("id")
				}
			}, 
			indicator : "<img src='/images/icon-loading.gif'>",
			tooltip   : "Click to edit...", 
			type	  : "textarea",
			submit  : 'Update',
			style  : "inherit"
		}); 
		
		$('.admin-address span, #fb_link, #twitter_link').editable("/admin/updatecontent", {
			submitdata: function(){
            	return {
					item: $(this).attr("id")
				}
			}, 
			indicator : "<img src='/images/icon-loading.gif'>",
			tooltip   : "Click to edit...", 
			type	  : "text",
			submit  : 'Update',
			style  : "inherit"
		}); 
	}  
	
	if ($('#admin-testimonials').length>0) {
		$('.tm-name, .tm-from').editable("/admin/updatetestimonials", { 
			submitdata: function(){
				return {
					id:   $(this).closest(".admin-testimonials-wrapper").attr('class').split(' ')[1] , 
					item: $(this).attr("class")
				}
			},
			indicator : "<img src='/images/icon-loading.gif'>",
			tooltip   : "Click to edit...", 
			type	  : "text",
			submit  : 'Update',
			style  : "inherit"
		});  
		
		$('.tm-value').editable("/admin/updatetestimonials", { 
			submitdata: function(){
				return {
					id:   $(this).closest(".admin-testimonials-wrapper").attr('class').split(' ')[1] , 
					item: $(this).attr("class")
				}
			},
			indicator : "<img src='/images/icon-loading.gif'>",
			tooltip   : "Click to edit...", 
			type	  : "textarea",
			submit  : 'Update',
			style  : "inherit"
		});
			 
		
	}; 
		
	globalVars={
		init:function(){
			this.initially=[];
			for(var i in window){this.initially[i]=true};
		},
		get:function(x){
			var win=window;
			if(navigator.userAgent.toLowerCase().indexOf("msie")>=0 && window["ActiveXObject"]){
				win=this.ieFix()
			};
			var obj={};
			var ffx=',addEventListener,location,document,navigator,event,frames,';
			for(var i in win){
				if(i=="fullScreen"){continue};
				var a=true;
				if(this.initially[i] && typeof window[i]=="function"){this.initially[i]=false};
				a=a && !(this.initially[i]);
				a=a && !(window[i]===undefined);
				a=a && !(window[i]===null);
				a=a && (i.indexOf("[[")<0);
				a=a && (window[i]+"").indexOf("[native code]")<0;
				var b=a;
				a=a && (window[i]+"").indexOf("[object HTML")!=0;
				if(a!=b){b=false} else {b=true};
				a=a && (window[i]+"").indexOf("[object Window")!=0;
				a=a && (window[i]+"").indexOf("[object]")!=0;
				a=a && ((window[i]+"")!="[function]");
				a=a && ((window[i]+"")!="(Internal Function)");
				a=a && (!(typeof window[i]=="function" && (window[i]+"").indexOf("<Logger")==0));
				a=a && (i!="NaN");
				a=a && (i!="0" && i!="1");		
				a=a && (i!="Infinity");
				a=a && (i!="Math");
				a=a && ffx.indexOf(','+i+',')<0;
				if(x && b){a=true};
				if (a){obj[i]=window[i]};
			};
			obj["globalVars"]=globalVars;
			if(window["onload"]){obj.onload=onload};
			return obj
		},
		getAll:function(){return this.get(true)},
		getOwn:function(){return this.get()},
		print:function(){
			var win=globalVars.getOwn();  /*returns an object containing global variables*/
			var x=[];
			for (var i in win){x.push(i)};
			console.log(x.join("\n"));
		},
		printAll:function(){
			var win=globalVars.getAll(); 
			var x=[];
			for (var i in win){x.push(i)};
			console.log(x.join("\n"));
		}
	};    
	
	globalVars.init();
	
	
	//buttons
	//var timer_before, timer_after, timer_total;
	//timer_before=new Date();
	$('.ui-button-apperence').button();
	//timer_after=new Date();
	//console.log("button time: "+(timer_after-timer_before)+" ms");
})