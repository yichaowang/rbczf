window.addEvent('domready', function() {
	if ($('enrollment')!=null){
		$$('#program-tb tr[class!=title]').each(function(el){
			el.set('morph',{duration: 'short'});
			el.addEvents({
				mouseenter: function(){
					el.addClass('hover');
					this.morph({
						'color':'#fff',
						'background-color': '#999'
					});
				},
				mouseleave: function(){
					el.removeClass('hover');
					this.morph({
						'color':'#000',
						'background-color': '#fff'
					});
				},
				click: function(){
					new Request.JSON({
						url: 'http://rbczf.local/admin/programusers?format=json',
						onRequest: function(){
							
						},
						onSuccess: function(response){
							$$('#user-tb tr[class!=title]').destroy();
							response['users'].each(function(el){
								var row = new Element('tr.'+el.id, {
									'html':'<td>'+el.fname+" "+el.lname +'</td>'
								}).inject('user-tb');
							})
						}                         
					}).get({'pid':el.get('class')}); 
				}
			})
		})
	}
});