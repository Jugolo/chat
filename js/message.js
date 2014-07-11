var Message = (function(){

    Message.prototype.Main   = null;
    Message.prototype.number = 0;

    function Message(controler){
        this.Main = controler;
    }

     Message.prototype.bot_message = function(msg,mid,color,langKey,langData){
         this.add_message({
             'time' : this.Main.get_time(),
             'message' : msg,
             'mid' : mid,
             'isBot' : true,
             'obj' : {
                 'messageColor' : (typeof color === "undefined" ? this.Main.data.systemBot.text : color),
                 'langKey'      : (typeof langKey === "undefined" ? null : langKey),
                 'langData'     : (typeof langData === "undefined" ? null : langData)
             }
         },"bot");
    };

    Message.prototype.add_user_message = function(msg){
        this.add_message({
            'time'    : this.Main.get_time(),
            'message' : msg.message,
            'mid'     : msg.id,
            'isBot'   : false,
            'obj'     : msg
        });
    };

    Message.prototype.private_message = function(msg,mid,obj){
      this.add_message({
          'time' : this.Main.get_time(),
          'message' : msg,
          'mid'     : mid,
          'isBot'   : false,
          'obj'     : obj
      },'msg');
    };

    Message.prototype.add_message = function(data,type){
        this.number++;
        var color = this.Main.data.boxColor[this.number%2];
        var message = "";
        message = "<div class='message' style='background-color: "+color+";' mid='"+data.mid+"'> "
        + "<span class='time'>["+data.time+"]</span> ";
        if(type == "bot"){
            message += "<span class='from' style='color:"+this.Main.data.systemBot.color+";'><i>"+this.Main.data.systemBot.name+":</i></span> ";
        }else if(type == "msg"){
            message += "<spam class='m_container' style='color:yellow'>"+this.Main.add_default_item_to_message(this.Main.add_smylie_to_string(data.message))+"</spam>";
        }else{
            message += "<span class='user_img'><img src='"+data['obj'].img+"' style='width:15px;height:15px'></span> "
                    +  "<span class='from'><i>"+data['obj'].nick+":</i></span> ";
        }
        if(type != "msg"){
            message += "<span class='m_container' style='color:"+data['obj'].messageColor+"'"+this.addBotItems(data)+">"+this.Main.add_default_item_to_message(this.Main.add_smylie_to_string(data.message))+"</span> ";
            + "</div>";
        }else{
            message += "</div>";
        }

        this.Main.getDom("main").append(message);
        //vi scrooler nu tilbage til bunden :)
        this.Main.getDom("main").getObject().scrollTop = this.Main.getDom("main").getObject().scrollHeight;
    };

    Message.prototype.addBotItems = function(data){
        if(!data.isBot){
            return "";
        }

        if(data['obj']['langKey'] === null && data['obj']['langData'] === null){
            return "";
        }

        return " langString='"+data['obj']['langKey']+"' langData='"+JSON.stringify(data['obj']['langData'])+"'";
    }

    return Message;
})();
