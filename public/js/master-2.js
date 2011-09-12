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
		// Test source, list of tags from http://del.icio.us/tag/
			var tokens = ['.net', '2008', '3d', 'advertising', 'ajax', 'appengine', 'apple', 'architecture', 'art', 'article', 'articles', 'audio', 'blog', 'blogging', 'blogs', 'books', 'business', 'china', 'community', 'computer', 'cooking', 'cool', 'css', 'culture', 'design', 'development', 'diy', 'download', 'education', 'english', 'environment', 'fashion', 'fic', 'film', 'finance', 'firefox', 'flash', 'flex', 'flickr', 'fonts', 'food', 'free', 'freeware', 'fun', 'funny', 'gallery', 'games', 'generator', 'git', 'google', 'government', 'graphics', 'green', 'hardware', 'health', 'history', 'home', 'howto', 'humor', 'illustration', 'imported', 'inspiration', 'interactive', 'internet', 'java', 'javascript', 'job', 'jobs', 'language', 'law', 'library', 'linux', 'mac', 'maps', 'marketing', 'math', 'media', 'microsoft', 'mobile', 'money', 'mp3', 'music', 'news', 'online', 'opensource', 'osx', 'photo', 'photography', 'photos', 'photoshop', 'php', 'politics', 'portfolio', 'productivity', 'programming', 'python', 'rails', 'recipe', 'recipes', 'reference', 'research', 'resources', 'ruby', 'school', 'science', 'search', 'security', 'seo', 'sga', 'shopping', 'slash', 'social', 'software', 'statistics', 'teaching', 'tech', 'technology', 'tips', 'todo', 'tools', 'toread', 'travel', 'tutorial', 'tutorials', 'tv', 'twitter', 'typography', 'ubuntu', 'video', 'visualization', 'web', 'web2.0', 'webdesign', 'webdev', 'wiki', 'windows', 'wordpress', 'work', 'writing', 'youtube'];

			// Our instance for the element with id "demo-local"
			new Autocompleter.Local('add-user-to-program', tokens, {
				'minLength': 1, // We need at least 1 character
				'selectMode': 'type-ahead', // Instant completion
				'multiple': true // Tag support, by default comma separated
			});
		
	}
});