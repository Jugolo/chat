window.onload = start_chat;
var wsr = null;
var unsuccessfulLogin = 0;
var config = [];

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
	// wee send a get request to status.php to see what wee should do here :)
	get("status.php", function(ajax) {
		var json = ajax.toJson();
		config = json["config"];
		if (json['isWebSocket']) {
			startWebSocket(json["host"], json["port"]);
		} else {

		}
	});
}

function startWebSocket(host, port) {
	websocket = new WebSocket("ws://" + host + ":" + port);

	websocket.onerror = function(evt) {
		console.log(evt.data);
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

	}
}

function handleGlobel(command, data) {
	console.log("'"+data+"'");
	switch (command) {
	case "LOGIN":
		handleLoginRespons(data);
		break;
	}
}

function handleLoginRespons(data) {
	if(data == "true"){
		unsuccessfulLogin = 0;
		var channels = config["startChannel"].split(",");
		for(i=0;i<channels.length;i++){
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