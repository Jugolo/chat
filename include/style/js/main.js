window.onload = start_chat;
var wsr = null;
var unsuccessfulLogin = 0;
var config = [];
var container = [];

var AjaxRespond = (function() {
	function AjaxRespond(obj) {
		this.obj = obj;
	}

	AjaxRespond.prototype.toText = function() {
		return this.obj.responseText;
	};

	AjaxRespond.prototype.toJson = function() {
		return JSON.parse(this.toText());
	};

	AjaxRespond.prototype.toXml = function() {
		return this.obj.responseXML;
	};

	return AjaxRespond;
})();

function appendTab(handler){
	function createTab(handler){
		var container = document.createElement("span");
		container.className = "chat_tab";
		
		if(typeof handler.icon !== "undefined"){
			//append icon
			var icon = document.createElement("img");
			icon.className = "chat_tab_icon";
			container.appendChild(icon);
		}
		
		//control for name
		if(typeof handler.name !== "undefined"){
			container.appendChild(document.createTextNode(handler.name));
		}
		
		//append it to the tab list
		document.getElementById("chat_tap_container").appendChild(container);
	}
	
	//create tab
	createTab(handler);
}

var ChannelHandler = (function(){
	function ChannelHandler(name){
		this.name = name;
		appendTab(this);
		//wee want to get a title on this channel :)
		send("TITLE: "+this.name);
	}
	
	ChannelHandler.prototype.onBlur = function(){
		
	};
	
	ChannelHandler.prototype.onFocus = function(){
		
	};
	
	return ChannelHandler;
})();

function get(url, callback) {
	var ajax = new XMLHttpRequest();
	ajax.open("GET", url, true);
	ajax.send();
	ajax.onreadystatechange = function() {
		if (this.readyState == 4 && this.status == 200) {
			callback(new AjaxRespond(this));
		}
	}
}

function start_chat() {
	console.log("Chat loading")
	// wee send a get request to status.php to see what wee should do here :)
	get("status.php", function(ajax) {
		var json = ajax.toJson();
		config = json["config"];
		if (json['isWebSocket']) {
			console.log("Chat connect to websocket");
			startWebSocket(json["host"], json["port"]);
		} else {

		}
	});
}

function startWebSocket(host, port) {
	websocket = new WebSocket("ws://" + host + ":" + port);

	websocket.onerror = function(evt) {
		console.log(evt.data);
		console.log("Error on websocket connecttion: "+evt.data);
	};

	websocket.onmessage = function(evt) {
		handleMessage(evt.data);
	};

	websocket.onopen = function() {
		console.log("WebSocket connection is now open");
		init_server();
	};

	websocket.onclose = function() {
		console.log("WebSocket connection is now closed");
	};

	wsr = websocket;
}

function cookie(name) {
	var cookieParts = document.cookie.split(";");
	for (i = 0; i < cookieParts.length; i++) {
		var data = cookieParts[i].split("=");
		if (data[0].trim() == name) {
			return data[1].trim();
		}
	}

	return null;
}

function init_server() {
	var elemtns = document.getElementsByClassName("not_loaded");
	for(var i=0;i<elemtns.length;i++){
		var item = elemtns[i];
		var names = item.className.split(" ");
		names.splice(names.indexOf("not_loaded"), 1);
		item.className = names.join(" ");
	}
	login();
}

function login() {
	send("LOGIN: " + cookie("identify"));
}

function handleMessage(msg) {
	console.log("[S]" + msg);
	var first = msg.substr(0, msg.indexOf(": ")).split(" ");
	var data = msg.substr(msg.indexOf(": ")+2);

	if (first.length == 1) {
		handleGlobel(first[0], data);
	} else {
		handleChannelCommand(first[0], first[1], data);
	}
}

function handleChannelCommand(command, channel, data){
	switch(command){
	case "JOIN":
		handleJoin(channel, data);
		break;
	}
}

function handleJoin(name, user){
	var channel = getChannel(name);
	if(channel == null){
		//it is my join
		newChannel(name);
	}else{
		
	}
}

function getChannel(name){
	for(var i=0;i<container.length;i++){
		var c = container[i];
		if(c instanceof ChannelHandler && c.name == name){
			return c;
		}
	}
	
	return null;
}

function handleGlobel(command, data) {
	switch (command) {
	case "LOGIN":
		handleLoginRespons(data);
		break;
	}
}

function newChannel(name){
	container.push(new ChannelHandler(name));
}

function handleLoginRespons(data) {
	if(data == "true"){
		unsuccessfulLogin = 0;
		var channels = config["startChannel"].split(",");
		//remove the channels wee alredy are in :)
		var current = inChannels();
		var append  = {};
		for(var i=0;i<current.length;i++){
			if(channels.indexOf(current[i]) != -1){
				channels.splice(channels.indexOf(current[i]), 1);
			}
			newChannel(current[i]);
		}
		
		//okay let us join the main channels :)
		for(var i=0;i<channels.length;i++){
			send("JOIN: "+channels[i]);
		}
		return;
	}
	
	if(unsuccessfulLogin == 5){
		console.log("Failed to login. all message to server will not be set");
		return;
	}
	
	console.log("Login failed. will try agin in 5 seconds");
	unsuccessfulLogin++;
	setTimeout(function(){
		login();
	}, 5000);
}

function send(str) {
	console.log("[C]" + str);
	if (wsr != null) {
		wsr.send(str);
	}
}