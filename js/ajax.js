var JAjax = (function () {
	
	this.data = null;
	this.obj  = null;
    this.isOpen = false;
	
    function JAjax(data) {
        this.initAjax();
        data = this.initData(data);
        if(typeof data.action === "undefined"){
        	return;
        }
		
		this.data = data;
        
    }
	
	JAjax.prototype.changePost = function(post){
		this.data.send = post;
	};
	
	JAjax.prototype.changeMethod = function(method){
      var m = "get";
      if(method == "post"){
          m = "post";
      }

        this.data.method = m;
    };

    JAjax.prototype.changeUrl = function(url){
        this.data.action = url;
    };
	
	JAjax.prototype.enable = function(){
		if(this.data.protokol == "socket"){
			this.enableSocket();
		}else{
			this.doAction(this.data);
		}
	};

    JAjax.prototype.get_cookie = function(){
        var cookie_string = document.cookie;
        console.log(cookie_string);
        return cookie_string;
    };
	
	JAjax.prototype.enableSocket = function(){
		if("WebSocket" in window){
		var uri = "ws://"+this.data.host+":"+this.data.port;
		this.obj = new WebSocket(uri);
		var $this = this;
			
		this.obj.onopen = function(){
            $this.isOpen = true;
		};
			
		this.obj.onmessage = function(evt){
			$this.handleAnswer(evt.data);
		};
			
	    this.obj.onerror = function(evt){
			$this.data.error(evt.data);
		};
		
		this.obj.onclose = this.data.onclose;
		}else{
			alert("WebSocket not support in you browser upgrade it please");
		}
	};
	
	JAjax.prototype.send = function(message){
        if(typeof message === "undefined"){
            return;
        }
		var jsons = JSON.stringify(message);
        this.obj.send(jsons);
	};
	
    JAjax.prototype.doAction = function (data) {
        if (data['method'].toLowerCase() == 'post') {
			try{
            this.object.open('POST', data['action'], true);
			}catch(e){
				data.error(e.message);
				return;
			}
            this.object.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            if (typeof data['send'] != 'undefined') {
                this.object.send(this.initSend(data['send']));
            } else {
                this.object.send();
            }
        } else {
            this.object.open('GET', data['action'], true);
            this.object.send();
        }

        if (typeof data['success'] != 'undefined') {
            var $this = this;
            this.object.onreadystatechange = function () {
				if($this.object.readyState == 4 && $this.object.status == 200){
					$this.handleAnswer($this.object.responseText);
				}
            };
        }
    };
    
    JAjax.prototype.onError = function(status,data){
        if(typeof this.data.error !== "function"){
            data.error = function(status){alert(status);};
        }
        
        this.data.error(status);
    };

    JAjax.prototype.handleAnswer = function (text) {
            try{
               var json = JSON.parse(text);
            }catch(e){
               this.onError("json failed in JAjax class data: "+text,data);
               return;
            }

            //control if wee have it all ;)
            if (typeof json['location'] != 'undefined') {
                window.location.href = json['location'];
            } else if (typeof json['throw'] != 'undefined') {
                throw json['throw'];
			}else if(typeof json.alert !== 'undefined'){
				alert(json.alert);
			}
            
            this.data['success'](json);
    };

    JAjax.prototype.initSend = function (send) {
        if (typeof send == 'object') {
            var s = "";
            var i = 0;
            for (var key in send) {
                s += (i != 0 ? '&' : '') + encodeURIComponent(key) + "=" + encodeURIComponent(send[key]);
                i++;
            }
            return s;
        }
    };

    JAjax.prototype.initData = function (data) {
        if(typeof data === "undefined"){
            alert("Data is empty");
        }

        if (typeof data['method'] == 'undefined') {
            data['method'] = "post";
        }

        if (typeof data['action'] == 'undefined') {
            alert("Missing action in JAjax!");
            return false;
        }
		
		if(typeof data['protokol'] === "undefined"){
			data.protokol = "ajax";
		}
		
		if(typeof data.success === "undefined"){
			data.success = function(data){};
		}
		
		if(typeof data['onclose'] === "undefined"){
			data.onclose = function(){};
		}
		
        return data;
    };

    JAjax.prototype.close_socket = function(){
        this.obj.onclose = function(){};//do nothing ;)
        this.obj.close();
    };

    JAjax.prototype.initAjax = function () {
        this.object = new XMLHttpRequest();
    };
    return JAjax;
})();