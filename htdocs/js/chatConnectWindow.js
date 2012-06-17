// WebChat2.0 Copyright (C) 2006-2007, Chris Chabot <chabotc@xs4all.nl>
// Licenced under the GPLv2. For more info see http://www.chabotc.com

chatConnectWindow = $.klass();
$.extend($.extend(chatConnectWindow.prototype, chatWindow.prototype), {
	initialize: function(id) {
		this.windowInitialize(id, arguments[1] || {});
		this.divNickname       = this.id+'_nickname_input';
		this.divServerPassword = this.id+'_serverPassword_input';
		this.divNetwork        = this.id+'_network_select';
		this.localServers      = ['127.0.0.1'];
		that                   = this;
		$("#" + this.divContent).html('<div class="list_content">'+
		                               'Enter your nickname:<br />'+
		                               '<div class="nickname_input" id="'+this.divNickname+'"><input type="text" name="input_nickname" id="input_nickname" /></div><br />'+
		                               'Enter server password:<br />'+
		                               '<div class="serverPassword_input" id="'+this.divServerPassword+'"><input type="password" name="input_serverPassword" id="input_serverPassword" /></div><br />'+
		                               '<div class="network_select" id="'+this.divNetwork+'"><input type="hidden" name="input_server" value="irc.sturmrune.de" id="input_server" /></div><br />'+
		                               '<div class="button" id="connect_button"><div class="button_left"></div><div class="button_center"><div class="button_text">Connect</div></div><div class="button_right"></div></div>'+
		                               '</div>');
		this.setTitle('Connect');
		$('#connect_button').click(function(){that.onConnect()});
	},

	onConnect: function(event) {
		if (event != undefined && event && event.stopPropagation != undefined) {
			event.stopPropagation();
		}
		var server   = $('#input_server').val();
		var nickname = $('#input_nickname').val();
		var pass = $('#input_serverPassword').val();

		console.log(server);
		console.log(nickname);
		console.log(pass);

		if (server && nickname != '' && nickname != undefined) {
			console.info("here");
			chat.connect(nickname, server, pass);
		}
	}
});