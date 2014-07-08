var system = null;
var System = (function () {

    System.prototype.option_name = {'online': 'online', 'setting': 'user', 'smylie': 'smyile'};
    System.prototype.object = {};
    System.prototype.smylie = [];
    System.prototype.is_socket = false;
    System.prototype.user_config = {};
    System.prototype.chat_name   = null;
    System.prototype.user_in_channel = [];
    System.prototype.null = "";
    System.prototype.li = 1;
    System.prototype.liCache = [];
    System.prototype.timer = null;
    System.prototype.ajax = [];
    System.prototype.ping_send = {'command' : null, 'channel' : null};
    System.prototype.u_num = 0;
    System.prototype.channelCount = 0;//count number of channels :)

    //config tag ;)
    System.prototype.valid_config = ['sound','textColor','lang','time'];

    function System(data) {
        this.data = data;
        this.setDom();
        //vi sikre os at vi har tømt chatten inden vi går igang ;)
        this.unset_screen();
        //vi sætter nu vent venligst billed ind ;)
        this.set_background("lib/vent.png");
        //vi henter nu sprog
        this.set_lang();
        //vi sætter nu status
        this.new_status();
        //vi indstiller nu ajax ;)
        if(this.get_protokol() != "ajax"){
            this.new_ajax();
        }
        //vi sætter nu message ;)
        this.init_message();
        //vi sætter nu cache
        this.init_cache();
        //vi sætter nu lyd
        this.init_sound();

        //vi cloner JAjax isOpen
        if(this.get_protokol() != "ajax"){
            this.new_ajax_query();
            this.is_socket = this.ajax[0].isOpen;
        }
        this.li = this.data.li;

        if (this.get_protokol() == "socket") {
            this.prepare_start_socket();
            window.onbeforeunload = function(){

            };
        }else{
            this.new_ajax_query({
                'message' : '/getStatus',
                'channel' : 'Bot'
            },'server.php?userBlock=' + this.data.browserBlock+'&sort='+this.data.user.sort+"&li=" + this.get_last_id());
        }
    }

    System.prototype.init_sound = function(){
        this.set_object("sound",new Sound(this.getDom("newInChanMessage")));
        this.get_object("sound").maxTime(1);
    };

    System.prototype.get_last_id = function(){
        return this.li;
    };

    System.prototype.update_last_id = function(id){
        if(parseInt(id) > parseInt(this.get_last_id())){
            this.li = parseInt(id);
        }
    };

    System.prototype.closhe_socket = function(){
      if(this.get_protokol() != "socket"){
          return;
      }

      this.new_ajax_query({
          'message' : '/exit',
          'channel' : 'Bot'
      })
    };

    System.prototype.send_ping = function(command_to_save,channel){
        this.new_ajax_query({
            'message' : '/ping',
            'channel' : 'Bot'
        },'server.php?userBlock='+this.data.browserBlock+'&sort='+this.data.user.sort+"&li="+this.get_last_id());

        if(typeof command_to_save !== "undefined" && typeof channel !== "undefined"){
            this.ping_send = {
                'command' : command_to_save,
                'channel' : channel
            };
        }
    }

    System.prototype.onKeyPressWrite = function(e){
        if(e.keyCode == 13){
            e.preventDefault();
            var c= new Command(this.getDom("textbox").context());
            if(c.isCommand() && c.get() === "join"){
                this.send_ping(this.getDom("textbox").context(),"Bot");
                this.getDom('textbox').empty();
                return;
            }
            this.stop_chat();
            this.timer = 0;
            this.new_ajax_query({
                'message' : this.getDom("textbox").context(),
                'channel' : this.chat_name
            },'server.php?userBlock=' + this.data.browserBlock+'&sort='+this.data.user.sort+"&li=" + this.get_last_id());

            this.getDom("textbox").empty();
        }
    };

    System.prototype.clickConfig = function(key,value){
        if(this.valid_config.valueOf(key) == -1){
            //config findes ikke!
            this.get_object("message").bot_message(this.convert_lang_string("config_not_valid",{'config' : key}),"mis");
            return null;
        }

        this.new_ajax_query({
            'message' : '/config ['+key+']['+value+']',
            'channel' : this.chat_name
        },'server.php?userBlock=' + this.data.browserBlock+'&sort='+this.data.user.sort+"&li=" + this.get_last_id());
    };

    System.prototype.get_my_stat = function(){
        return this.data.user;
    };

    System.prototype.handle_in_message = function (json,is_from_cache) {
        if (typeof json === "undefined" || typeof json.message === "undefined") {
            this.get_object("status").error();
            return;
        }

        if(this.timer !== null){
            this.stop_chat();
        }

        this.get_object("status").okay();

        for (var i = 0; i < json.message.length; i++) {
            var msg = json.message[i];

            if(!is_from_cache){
                if(this.liCache.lastIndexOf(msg['id']) != -1){
                    continue;
                }
            }

            if(!is_from_cache){
                this.update_last_id(msg['id']);
                this.liCache.push(msg['id']);
            }

            if (msg.cid == 1 && !is_from_cache) {
                this.handle_prodcast_message(msg);
            } else {
                if(this.user_config.sound == true || this.user_config.sound == 'true'){
                    this.get_object("sound").play();
                }
                if(!is_from_cache){
                    this.get_object("cache").add(msg.cid, msg);//vi har nu tilføget denne besked til cache ;)
                }
                this.handle_nomal_msg(msg,is_from_cache);
            }
        }

        if(this.timer !== null){
            this.start_chat();
        }
    };

    System.prototype.handle_prodcast_message = function(msg){
        var command = new Command(msg.message);
        if(!command.isCommand()){
            alert("Server sent brodcast and it was not a command \r\n"+msg.message);
            return;//wee only care about command ;)
        }

        switch(command.get()){
            case 'cookieOkay':
                this.new_ajax_query({
                    'message' : '/getStatus',
                    'channel' : 'Bot' //this is prodcast msg!
                });
            break;
            case 'config':
                var data = command.get_param();
                var block = data[2].split(";");
                for(var i=0;i<block.length;i++){
                    if(/^(.*?)=(.*?)$/.test(block[i])){
                        var con = /^(.*?)=(.*?)$/.exec(block[i]);
                        this.user_config[con[1]] = con[2];
                    }
                }

                this.update_config();
            break;
            case 'profilImage':
                var data = command.get_param();
                this.getDom("avatar").css("backgroundImage","url('"+data[2]+"')");
                this.new_ajax_query({
                    'message' : '/getLang',
                    'channel' : 'Bot'
                },'server.php?userBlock=' + this.data.browserBlock+'&sort='+this.data.user.sort+"&li=" + this.get_last_id());
            break;
            case 'langList':
                var block = command.get_param();
                if(block === null){
                    alert(msg['message']);
                    return;
                }

                block = block[2].split(",");
                for(var i=0;i<block.length;i++){
                    this.getDom("lang_select").setOption(block[i],block[i]);
                }

                //vi opretter nu forbindelse til en channel :)
                this.new_ajax_query({
                    'message' : '/join '+this.start_channel(),
                    'channel' : 'Bot'
                },'server.php?userBlock=' + this.data.browserBlock+'&sort='+this.data.user.sort+"&li=" + this.get_last_id());
                this.open_chat();
            break;
            case 'updateConfig':
                this.update_my_config(msg,command);
            break;
            case 'kick':
                //jeg er blevet kicket og får ikke længere beskeder fra serveren hmm
                var from = null;
                var lang = null;
                if(/^\/kick\s(#[a-zA-Z]*?)\s(.*)$/.test(msg['message'])){
                    var data = /^\/kick\s(#[a-zA-Z]*?)\s(.*?)$/.exec(msg['message']);
                    from = data[1];
                    lang = this.convert_lang_string('onKickMsg',{
                        'channel' : data[1],
                        'msg'     : data[2]
                    });
                }else{
                    from = command.get_param()[2];
                    lang = this.convert_lang_string('onKick',{
                        'channel' : from
                    });
                }
                alert(lang);
                //this is good for ajax trust me :D
                this.new_ajax_query({
                    'message' : '/ping',
                    'channel' : 'Bot'
                },'server.php?userBlock=' + this.data.browserBlock+'&sort='+this.data.user.sort+"&li=" + this.get_last_id());
                this.leave_channel(from,false,true);
            break;
            case 'ban':
                var channel = this.trim(command.get_param()[2]);
                this.leave_channel(channel,false);
                alert(this.convert_lang_string("onMyBan",{
                    'from' : msg['nick']
                }));
            break;
            case 'bannet':
                //brugeren forsøger nu at tilgå en channel han er bannet i. hmm det må vi hellere gøre noget ved :D
                var from = this.trim(command.get_param()[2]);
                //er det en start channel
                if(this.start_channel() == from){
                    //hmm okay
                    this.get_object("message").bot_message(this.convert_lang_string("banOnStartChan",{}),"red");
                }else{
                    this.get_object("message").bot_message(this.convert_lang_string("isBannet",{
                        'channel' : from
                    }),"red");
                }
            break;
            case 'leave':
                var channel = this.trim(command.get_param()[2]);
                this.leave_channel(channel);
                alert(this.convert_lang_string("onMyLeave",{
                    'channel' : channel
                }));
            break;
            case 'join':
                //this.join_in_channel(msg,command);
            break;
            case 'pong':
                if(this.ping_send.command !== null && this.ping_send.channel !== null){
                    this.new_ajax_query({
                        'message' : this.ping_send.command,
                        'channel' : this.ping_send.channel
                    },'server.php?userBlock=' + this.data.browserBlock+'&sort='+this.data.user.sort+"&li=" + this.get_last_id());
                    this.ping_send = {
                        'command' : null,
                        'channel' : null
                    };
                }
            break;
        }
    };

    System.prototype.trim = function(string){
        if(typeof string !== "string"){
            if(typeof string === "number"){
                return string;//number is okay to just past :)
            }
            return this.null;
        }
        return string.replace(/^\s+|\s+$/g,"");
    }

    System.prototype.handle_nomal_msg = function (msg,is_from_cache) {
        //vi kontrollere om vi har en besked ( det er det vigtigste her ;))
        if(typeof msg['message'] === "undefined"){
            console.log(msg);
          return false;
        }
        var command = new Command(msg.message);
        if (command.isCommand()) {
            //vi har nu en command at gøre :)
            this.handle_command(msg, command,is_from_cache);
        }else{
            //vi har en ganske almindligt besked ;)
            if(msg['isBot'] == this.data['yesNo'].yes){
                this.get_object("message").bot_message(msg['message'],msg['id'],msg['messageColor']);
            }else{
                this.get_object("message").add_user_message(msg);
            }
        }
    };

    System.prototype.handle_command = function (msg, command, is_from_cache) {
        switch(command.get()){
            case 'join':
                this.join_in_channel(msg,command,is_from_cache);
            break;
            case 'title':
                if(this.is_in_this_channel(msg.channel)){
                    var block = command.get_param();
                    this.get_object("message").bot_message(this.convert_lang_string("onTitle",{
                        'title' : block[2],
                        'nick'  : this.data['systemBot'].name
                    }), msg.id,"green");
                    this.getDom("title").context(block[2]);
                }
            break;
            case 'error':
                this.get_object("message").bot_message("Error: "+command.get_param()[2],msg.id,"red");
            break;
            case 'online':
                var block = command.get_param()[2].split(" ");
                for(var i=0;i<block.length;i++){
                    var b = block[i].split("|");
                    //vi kontrollere i vores cache om vi har hver enkelt bruger :)
                    if(this.get_object("cache").add_users(b[1],b[0],msg.channel)){
                        this.add_user_in_user_list(b[2],b[1],b[0],b[3]);
                    }
                }
            break;
            case 'nick':
                if(!this.is_in_this_channel(msg.channel)){
                    return;
                }
                var old_nick = command.get_param()[2];
                this.get_object("message").bot_message(this.convert_lang_string("onNick",{
                    'oldNick' : old_nick,
                    'newNick' : msg.nick
                }),msg.id,"green");

                //vi opdatere nu nick i online listen :)
                this.getDom("option").run(function(){
                    if(this.attr("what") == system.option_name.online){
                        var users = this.getObject().getElementsByClassName("user");
                        J(users).run(function(){
                            if(this.attr("uid") == msg.uid){
                                this.getObject().getElementsByClassName("u_nick")[0].innerHTML = msg.nick;
                            }
                        });
                    }
                })
            break;
            case 'kick':
                var lang = null;
                if(/\/kick\s([a-zA-Z]*?)\s(.*)$/.test(msg['message'])){
                    var data = /^\/kick\s(.*?)$/.exec(msg['message']);
                    lang = this.convert_lang_string('onOKickMsg',{
                        'nick' : msg.nick,
                        'msg'  : data[1]
                    });
                }else{
                    lang = this.convert_lang_string("onOKick",{
                        'nick' : msg.nick
                    });
                }
                if(this.get_object("cache").remove_user_from_cache(this.trim(msg['uid']),this.trim(msg['channel']))){
                    this.get_object("message").bot_message(lang,msg.id,"red");
                    this.remove_user_from_online_list(msg['nick']);
                }else{
                    alert("Error user "+msg['nick']+" not found!");
                }
            break;
            case 'ban':
                if(this.get_object("cache").remove_user_from_cache(this.trim(command.get_param()[2]),this.trim(msg['channel']),true)){
                    this.get_object("message").bot_message(this.convert_lang_string("onBan",{
                        'from' : msg['nick'],
                        'too'  : command.get_param()[2]
                    }),msg['id'],"red");
                    this.remove_user_from_online_list(this.trim(command.get_param()[2]));
                }else{
                    alert("Error no user found");
                }
            break;
            case 'msg':
                var reg = /^\/msg\s(.*?)$/;
                if(reg.test(msg['message'])){
                    this.get_object("message").private_message(reg.exec(msg['message'])[1],msg['id'],msg);
                }else{
                    alert("Unknown msg command");
                }
            break;
            case 'exit':
                if(this.get_object("cache").remove_user_from_cache(this.trim(msg['uid']),this.trim(msg['channel']))){
                    this.get_object("message").bot_message(this.convert_lang_string('onExit',{
                        'nick' : msg['nick']
                    },msg['id'],"red"));
                    this.remove_user_from_online_list(this.trim(msg['nick']));
                }else{
                    alert("Error no user found");
                }
            break;
            case 'leave':
                if(this.get_object("cache").remove_user_from_cache(this.trim(msg['uid']),this.trim(msg['channel']))){
                    this.get_object("message").bot_message(this.convert_lang_string('onLeave',{
                        'nick' : msg['nick']
                    }),msg['id'],"red");
                    this.remove_user_from_online_list(this.trim(msg['uid']));
                }else{
                    alert("Error no user found");
                }
            break;
            case 'unban':
                this.get_object("message").bot_message(this.convert_lang_string('onUnban',{
                    'nick' : this.trim(command.get_param()[2])
                }),msg['id'],'green');
            break;
            case 'inaktiv':
                this.getDom("user").run(function(){
                   if(this.attr("uid") == msg['uid']){
                       var obj = this.getObject().getElementsByClassName("u_nick")[0];
                       obj.innerHTML = "<i>[i]"+command.get_param()[2]+"</i>";
                   }
                });
                //vi sender nu en besked om at brugeren er inaktiv :)
                this.get_object("message").bot_message(this.convert_lang_string('onInaktiv',{
                    'nick' : command.get_param()[2]
                }),msg['id'],'red');
            break;
            case 'notInaktiv':
                this.get_object("message").bot_message(this.convert_lang_string("onNInaktiv",{
                    'nick' : command.get_param()[2]
                }),msg["id"],'green');
                this.getDom("user").run(function(){
                   if(this.attr("uid") == msg['uid']){
                       this.getObject().getElementsByClassName("u_nick")[0].innerHTML = command.get_param()[2];
                   }
                });
            break;
            case 'maxFlood':
                this.get_object("message").bot_message(this.convert_lang_string("flood",{}),msg['id'],'red');
            break;
            case 'commandDenaid':
                this.get_object("message").bot_message(this.get_lang().WrongCommand,msg['id'],'red');
            break;
            case 'ignore':
                this.get_object("message").bot_message(this.convert_lang_string("onIgnore",{
                    'nick' : command.get_param()[2]
                }), msg['id'],'green');
            break;
            case 'unIgnore':
                this.get_object("message").bot_message(this.convert_lang_string("onUnIgnore",{
                    'nick' : command.get_param()[2]
                }),msg['id'],'green');
            break;
            default:
            break;
        }
    };

    System.prototype.update_my_config = function(msg,command){
        var conf = /^\/updateConfig\s(.*?)\s(.*?)$/.exec(msg.message);
        switch (conf[1]){
            case 'sound':
                this.getDom("sound_botton").getObject().selected = (conf[2] === "true" ? true : false);
                this.user_config[conf[1]] = (conf[2] == "true" ? true : false);
            break;
            case 'textColor':
                this.getDom("textbox").css("color",conf[2]);
                this.getDom("text_color").getOptions(function(value,text){
                   if(value == conf[2]){
                       this.getObject().slected=true;
                   }
                });
                this.user_config[conf[1]] = conf[2];
            break;
            case 'lang':
                this.set_object("Lang",LibLang[conf[2]]);
                this.getDom("lang_select").getOptions(function(value,text){
                    if(value == conf[2]){
                        this.getObject().slected = true;
                    }
                });
                this.user_config[conf[1]] = conf[2];
            break;
            case 'time':
                this.user_config.time_format = conf[2];
            break;
        }
    };

    System.prototype.join_in_channel = function(msg,command,is_cache){
        if(!this.is_num(msg['uid'])){
            return false;
        }
        var block = command.get_param();
        var channel = block === null ? this.trim(msg.channel) : this.trim(block[2]);
        //control if block is null ;)
        if(channel === this.null){
            alert("Channel is like nulll fuck");
            return;
        }else if(is_cache){
            //Kun for at være sikker!
            if(this.is_in_this_channel(msg.channel)){
                this.add_user_in_user_list(msg['img'],msg['nick'],msg['uid']);
                this.get_object("message").bot_message(this.convert_lang_string("onJoin",{'nick' : msg['nick']}),msg['id'],"green");
            }
        }else if(this.get_object("cache").add_users(this.trim(msg['nick']),this.trim(msg['uid']),channel)){//channel er allerede trimmet

            var start_stat = {
                'is_in' : this.is_in_this_channel(msg['channel']),
                'is_i'  : (msg['uid'] == this.get_my_stat().id)
            };

            if(start_stat.is_in){
                this.add_user_in_user_list(msg['img'],msg['nick'],msg['uid']);
                if(msg.cid != 1){//1 er en global channel som alle vigtige informationer bliver skrevet ind i. derfor gøre vi intet med den når det gælder at informere brugeren om det :D
                    this.get_object("message").bot_message(this.convert_lang_string("onJoin",{'nick' : msg['nick']}),msg['id'],"green");
                }
                //alert("Yes");
            }else if(start_stat.is_i){
                this.add_channel_botton(msg['cid'],channel);
                this.channelCount++;//ad one channel becuse wee is now in new channel :)
                this.switch_channel(channel,msg['cid']);
                this.new_ajax_query({
                    'message' : "/getOnline "+msg['channel'],
                    'channel' : this.chat_name
                },'server.php?userBlock=' + this.data.browserBlock+'&sort='+this.data.user.sort+"&li=" + this.get_last_id());
            }
        }else{
            if(msg['uid'] == this.get_my_stat().id){
                alert("Somting is broken!!!");
            }
        }
    };

    System.prototype.re_paint_channel_botton = function(on){
        var color = this.get_channel_botton_color();
        J(".chanenlContainer").run(function(){
           if(this.attr("cid") == on){
               this.css("backgroundColor",color.thisChannel);
           }else{
               this.css("backgroundColor",color.notThisChannel);
           }
        });
    };

    System.prototype.add_channel_botton = function(id,name){
        var botton = "<span class='chanenlContainer' cid='"+this.trim(id)+"'> "
        + "<span class='channelNameContainer' onclick='system.switch_channel(\""+this.trim(name)+"\",\""+id+"\")'>"+this.trim(name)+"</span> "
        + "<span onclick='system.on_closhe_channel_botton(\""+id+"\");'><img style='width:15px;height:15px;' src='../../images/no.png'></span> "
        + "</span> ";

        this.getDom("channel_list").append(botton);
    };

    System.prototype.on_closhe_channel_botton = function(id){
      this.getDom("channel_botton").run(function(){
          if(this.attr("cid") === id){
              //vi har nu fået fat i den rigtige channel ;)
              var name = this.getObject().getElementsByClassName("channelNameContainer")[0].innerHTML;
              system.leave_channel(name);
          }
      })
    };

    System.prototype.leave_channel = function(name,send_leave,onKick){
        if(typeof send_leave === "undefined"){
            send_leave = true;
        }

        if(typeof onKick === "undefined"){
            onKick = false;
        }

        var is_botton_found = false;
        var cid             = 1;//1 is system brodcast channel. (channel all message some is not in normaly channel get into)

        //vi fjerner knappen fra oversigten ;)
        this.getDom("channel_botton").run(function(){
           var bname = this.getObject().getElementsByClassName("channelNameContainer")[0].innerHTML;
            if(name == system.trim(bname)){
                cid = this.attr("cid");
                is_botton_found = this.remove();
            }
        });

        if(!is_botton_found){
            alert("Channel not found");
            return;
        }

        //vi tømmer cache
        this.get_object("cache").empty_cache(name,cid);
        this.channelCount--;
        //wee got now one less channel wee are in :D

        if(this.is_in_this_channel(name)){
            //vi fjerner nu cache af bruger i denne channel
            this.user_in_channel = [];
            //vi tømmer først main ;)
            this.getDom("main").empty();
            //tømmer bruger over sigten :)
            this.getDom("option").run(function(){
               if(this.attr("what") == system.option_name.online){
                   this.empty();
               }
            });
        }

        if(send_leave){
            //jeg forstår det slet ikke hmm :(
            this.new_ajax_query({
                'message' : '/leave',
                'channel' : this.trim(name)
            },'server.php?userBlock=' + this.data.browserBlock+'&sort='+this.data.user.sort+"&li=" + this.get_last_id());
        }

        //og vi finder en ny channel at være i :)
        var has_found_new_channel = false;
        var change_to_name        = null;
        var cid                   = 0;
        this.getDom("channel_botton").run(function(){
            change_to_name        = this.getObject().getElementsByClassName("channelNameContainer")[0].innerHTML;
            cid                   = this.attr("cid");
            has_found_new_channel = true;
        });

        if(has_found_new_channel){
            this.switch_channel(change_to_name,cid);
        }else{
            if(onKick){
                if(name == this.start_channel()){
                    this.chat_name = null;
                    this.stop_chat();
                    this.timer = null;
                    return false;//vi er nu igang med at joine en automatisk kick Hmmmm ikke en god ide :D
                }
            }
            this.chat_name = null;//lige nu er vi jo ikke i en channel!
            //this.send_ping('/join '+this.start_channel(),'Bot'); //this is a bad idé :(
        }

    };

    System.prototype.switch_channel = function(name,cid){
        //vi kontrollere at vi ikke allerede er i den (spild af kræfter)
        if(this.is_in_this_channel(name)){
            return;
        }

        this.chat_name = name;
        this.u_num = 0;


        this.getDom("main").empty();
        this.getDom("option").run(function(){
          if(this.attr("what") == system.option_name.online){
              this.empty();
          }
        });


        this.re_paint_channel_botton(cid);

        this.user_in_channel = [];

        this.handle_in_message({
            'message' : this.get_object("cache").get_message_in_channel(cid)
        },true);

        return;

    };

    System.prototype.show_user_menu = function(uid){
        this.getDom("user").run(function(){

            if(this.getObject().getElementsByClassName("user_menu")[0].style.display == 'block'){
                J(this.getObject().getElementsByClassName("user_menu")[0]).hide();
                return;
            }

           if(this.attr("uid") == uid){
               J(this.getObject().getElementsByClassName("user_menu")[0]).show();
           }else{
               J(this.getObject().getElementsByClassName("user_menu")[0]).hide();
           }
        });
    };

    System.prototype.add_user_in_user_list = function(img,nick,uid,inaktiv){
        this.user_in_channel.push(uid);
        this.getDom("option").run(function(){
           if(this.attr("what") == system.option_name.online){
               system.u_num++;
               var block = "<div class='user' uid='"+uid+"' style='background-color:"+(system.data.boxColor[system.u_num%2])+"'>"
               + "<div class='u_image'>"
                      + "<img src='"+img+"' style='width:20px;height:20px'>"
               + "</div>"
               + "<div onclick='system.show_user_menu(\""+uid+"\")' class='u_nick'>"
                      + (inaktiv == system.data.yesNo.yes ? '<i>[i]' : '')+nick+(inaktiv == system.data.yesNo.yes ? '</i>' : '')
               + "</div>"
               + "<div class='u_info_container'></div>"
               + "<div class='user_menu'>"
                   + "<table>";
                     if(system.get_my_stat().id == uid){
                         block += "<tr>"
                            + "<th langString='noOption' langData='{}'>"
                               + system.get_lang().noOption;
                            + "</th>"
                         + "</tr>";
                     }else{
                         block += "<tr>"
                               + "<th onclick='system.sendMessage(\"/ignore "+nick+"\")' langString='ignore' langData='{}'>"+system.get_lang().ignore+"</th>"
                            + "</tr>"
                            + "<tr>"
                               + "<th onclick='system.send_ping(\"/unIgnore "+nick+"\",system.chat_name)' langString='unIgnore' langData='{}'>"+system.get_lang().unIgnore+"</th>"
                            + "</tr>";
                     }
                   block += "</table>"
               + "</div>"
               + "<div>";

               this.append(block);

               return;
           }
        });
    };

    System.prototype.sendMessage = function(msg,channel){
        if(typeof channel === "undefined"){
            channel = this.chat_name;
        }

        this.new_ajax_query({
            'message' : msg,
            'channel' : channel
        },'server.php?userBlock=' + this.data.browserBlock+'&sort='+this.data.user.sort+"&li=" + this.get_last_id());
    }

    System.prototype.remove_user_from_online_list = function(nick){
        this.getDom("user").run(function(){
            if(system.is_num(nick)){
                if(nick == this.attr("uid")){
                    this.remove();
                }
            }else{
                var u_nick = this.getObject().getElementsByClassName("u_nick")[0].innerHTML;
                if(u_nick == nick){
                    this.remove();
                }
            }
        });
    };

    System.prototype.is_num = function(string){
        return /^[0-9]*?$/.test(string);
    };

    System.prototype.prepare_start_socket = function () {
        if (this.is_socket) {
            this.new_ajax_query({
                'message': '/cookie ' + this.data.cookie,
                'channel': 'Bot'
            });
            this.ping_socket();
        } else {
            this.is_socket = this.ajax[0].isOpen;
            if (!this.is_socket) {
                setTimeout(function () {
                    system.prepare_start_socket();
                }, this.data.timer);
            } else {
                this.prepare_start_socket();
            }
        }
    };

    System.prototype.get_time = function () {
        var date = new Date();
        if (typeof this.user_config.time_format === "undefined") {
            this.user_config.time_format = "H:i";
        }

        var use = this.user_config.time_format;

        var w_zerro = function (int) {
            if (int < 10) {
                return "0" + int;
            } else {
                return int;
            }
        };

        var day_of_year = function () {
            var now = new Date();
            var start = new Date(now.getFullYear(), 0, 0);
            var diff = now - start;
            var oneDay = 1000 * 60 * 60 * 24;
            var day = Math.floor(diff / oneDay);
            return day;
        };

        var week_of_year = function () {
            var d = new Date(+d);
            d.setHours(0, 0, 0);
            d.setDate(d.getDate() + 4 - (d.getDay() || 7));
            var yearStart = new Date(d.getFullYear(), 0, 1);
            var weekNo = Math.ceil(( ( (d - yearStart) / 86400000) + 1) / 7);
            return weekNo;
        };

        var h = date.getHours();

        //Day
        use = use.replace(/d/g, w_zerro(date.getDate()));
        use = use.replace(/D/g, this.get_lang().day_short[date.getDay()]);
        use = use.replace(/j/g, date.getDate());
        use = use.replace(/l/g, this.get_lang().day_long[date.getDay()]);
        use = use.replace(/N/g, (date.getDay() + 1));
        //s not supportet
        use = use.replace(/w/g, date.getDay());
        use = use.replace(/z/g, day_of_year());

        //Week
        use = use.replace(/W/g, week_of_year());

        //Month
        use = use.replace(/F/g, this.get_lang().month_long[date.getMonth()]);
        use = use.replace(/m/g, w_zerro((date.getMonth() + 1)));
        use = use.replace(/M/g, this.get_lang().months_short[date.getMonth()]);
        use = use.replace(/n/g, (date.getMonth() + 1));
        use = use.replace(/t/g, date.getDate());

        //Year
        use = use.replace(/L/g, (((date.getFullYear() % 4 == 0) && (date.getFullYear() % 100 != 0)) || (date.getFullYear() % 400 == 0) ? 1 : 0));
        //o not supportet
        use = use.replace(/Y/g, date.getFullYear());
        var fy = new String(date.getFullYear());
        use = use.replace(/y/g, fy.substring(2, 4));

        //Time
        use = use.replace(/a/g, (date.getHours() < 12 ? "am" : "pm"));
        use = use.replace(/A/g, (date.getHours() < 12 ? "AM" : "PM"));
        use = use.replace(/B/g, date.getMilliseconds());
        use = use.replace(/g/g, (h > 12 ? h - 12 : h));
        use = use.replace(/G/g, date.getHours());
        use = use.replace(/h/g, (h > 12 ? w_zerro((h - 12)) : w_zerro(h)));
        use = use.replace(/H/g, w_zerro(date.getHours()));
        use = use.replace(/i/g, w_zerro(date.getMinutes()));
        use = use.replace(/s/g, w_zerro(date.getSeconds()));
        use = use.replace(/u/g, date.getTime());

        //TimeZone not supportet ;)

        return use;
    };

    System.prototype.setSmylie = function (smy_name, smy_tag, smy_url, smy_reg_tag) {
        this.smylie[this.smylie.length] = {
            'smy_name'    : smy_name,
            'smy_tag'     : smy_tag,
            'smy_url'     : smy_url,
            'smy_reg_tag' : smy_reg_tag
        };
    };

    System.prototype.return_smylie_dom = function(){
      var html = "";
        for(var i=0;i<this.smylie.length;i++){
            var s = this.smylie[i];
            html += "<span style='cursor: pointer;' onclick='system.smy_click(\""+i+"\");'><img style='width:20px;height:20px' src='"+s['smy_url']+"' alt='"+s['smy_tag']+"' title='"+s['smy_name']+"'></span>\r\n";
        }

        return html;
    };

    System.prototype.add_smylie_to_string = function(string){
        if(typeof string === "undefined"){
            return this.null;
        }

        for(var i=0;i<this.smylie.length;i++){
            string = string.replace(new RegExp(""+this.smylie[i].smy_reg_tag+"","g"),"<img style='width:15px;height:15px;' src='"+this.smylie[i].smy_url+"'>");
        }
        return string;
    };

    System.prototype.jsoin_channel_by_click = function(name){
      this.new_ajax_query({
          'message' : '/join '+name,
          'channel' : this.chat_name
      },'server.php?userBlock=' + this.data.browserBlock+'&sort='+this.data.user.sort+"&li=" + this.get_last_id());
    };

    System.prototype.add_default_item_to_message = function(string){

        string = string.replace(/\[(img|image)=(.*?)\]/g,function(all,n,url){
            return "<img src='"+url+"' title='User bb image' class='bbImg'>";
        });


        string = string.replace(/\[url=(.*?)\](.*?)\[\/url\]/g,function(all,url,text){
            return "<a target='_blank' href='"+url+"' title='User bb link'>"+text+"</a>";
        });

        //finely :)
        return string.replace(new RegExp("(^|\s)#(\w*[a-zA-Z_]+\w*)",""),function(find){
           return "<span onclick='system.jsoin_channel_by_click(\""+find+"\")' style='cursor:pointer'>"+find+"</span>";
        });
    };

    System.prototype.smy_click = function(id){
      //vi skal du have fat i context i tekst box :)
        var context = this.getDom("textbox").context();
        var object  = this.getDom("textbox").getObject();
        var index   = context.length;
        if(object.selectionStart){
             index = object.selectionStart;
        }

        var put = {
            'befor' : context.substring(0,index),
            'after' : context.substring(index,context.length)
        };

        this.getDom("textbox").context(put.befor+this.smylie[id]["smy_tag"]+put.after);

        //okay now wee need to focus the text fild :D
        object.focus();
        var c = put.befor.length + this.smylie[id]["smy_tag"].length;
        if(object.createTextRange){
            var r = object.createTextRange();
            r.move('character',c);
            r.select();
        }else{
            object.selectionStart = c;
            object.selectionEnd   = c;
        }
    };

    System.prototype.init_message = function () {
        this.set_object("message", new Message(this));
    };

    System.prototype.set_lang = function () {
        this.set_object("lang", LibLang.English);
    };

    System.prototype.get_lang = function () {
        return this.get_object("lang");
    };

    System.prototype.new_status = function () {
        this.set_object("status", new Status(this.get_lang()));
    };

    System.prototype.new_ajax = function () {
        this.get_object("status").call();
        var id = 0;
        if(this.ajax.length <= 50){
            id = this.ajax.length;
        }
        this.ajax[id] = new JAjax({
            'action': 'server.php?userBlock=' + this.data.browserBlock+'&sort='+this.data.user.sort+"&li=" + this.get_last_id(),
            'method': 'get',
            'error': function (m) {
                if(system.get_protokol() == "ajax"){
                    system.get_object("message").bot_message(m,0);
                }else{
                    system.get_object("message").bot_message(system.get_lang().socket_open_fail, 0);
                }
                system.get_object("status").error();
            },
            'success': function (json) {
                system.get_object("status").call();
                system.handle_in_message(json,false);
            },
            'protokol': this.get_protokol(),
            'host': this.data['socket']['server'],
            'port': this.data['socket']['port']
        });
        return id;
    };

    System.prototype.new_ajax_query = function (post, url) {

        if (this.get_protokol() == "socket") {
            if (this.is_socket) {
                this.ajax[0].send(post);
            } else {
                this.ajax[0].enable();
            }

            return;
        }else{
            var id = this.new_ajax();
        }

        var block = "";

        if (typeof post !== "undefined") {
            this.ajax[id].changePost(post);
            block = "&isPost=true";
            this.ajax[id].changeMethod("post");
        }else{
            this.ajax[id].changeMethod("get");
        }

        if (typeof url !== "undefined") {
            this.ajax[id].changeUrl(url+block);
        }


        this.ajax[id].enable();
    };

    System.prototype.get_protokol = function () {
        if (this.data.protocol == 'socket') {
            return 'socket';
        }

        return 'ajax';
    };

    System.prototype.set_object = function (objName, objValue) {
        this.object[objName] = objValue;
    };

    System.prototype.get_object = function (objName) {
        if (typeof this.object[objName] === "undefined") {
            return null;
        }

        return this.object[objName];
    };

    System.prototype.set_background = function (url) {
        this.getDom("main").css("backgroundImage", "url('" + url + "')");
    };

    System.prototype.selectOption = function(obj){
      this.show_option(obj.value);
    };

    System.prototype.show_option = function (w) {
        this.getDom("option").run(function () {
            if (this.attr("what") == w) {
                this.show();
            } else {
                this.hide();
            }
        });
    };

    System.prototype.unset_screen = function () {
        this.getDom("main").empty();
        this.getDom("channel_botton").remove();
        this.show_option(this.option_name.online);

    };

    System.prototype.getDom = function (domName) {
        if (typeof this.dom[domName] === "undefined") {
            return null;
        }

        return this.dom[domName];
    };

    System.prototype.setDom = function () {
        this.dom = {
            'channel_list'     : J("#channelList"),
            'main'             : J("#channelMain"),
            'textbox'          : J("#textbox"),
            'setting'          : J("#rightContainer"),
            'channel_botton'   : J(".chanenlContainer"),
            'option'           : J(".option"),
            'sound_botton'     : J("#sound"),
            'text_color'       : J("#textColor"),
            'avatar'           : J("#profilImage"),
            'lang_select'      : J("#langSelect"),
            'title'            : J("#chattitle"),
            'user'             : J(".user"),
            'time'             : J("#time"),
            'newInChanMessage' : J("#newInChanMessage")
        };
    };

    System.prototype.init_cache = function () {
        this.set_object("cache", new Cache(this));
    };

    System.prototype.update_config = function(){
        this.getDom("sound_botton").getObject().checked = (this.user_config['sound'] == "true" ? true : false);

        this.getDom("text_color").getOptions(function(value,text,obj){
           if(value == system.user_config['textColor']){
               obj.selected = true;
           }
        });

        this.getDom("textbox").css("color",this.user_config['textColor']);

        if(typeof LibLang[this.user_config['lang']] === "undefined"){
            alert("Unknown lang: "+this.user_config['lang']);
        }else{
            this.set_object("lang",LibLang[this.user_config['lang']]);
            this.update_screen_lang();
            this.getDom("lang_select").getOptions(function(value,text,obj){
                if(value == system.user_config['lang']){
                    obj.selected = true;
                }
            });
        }

        this.getDom("time").context(this.user_config['time']);

    };

    /**
     * @param string [String] name of lang key
     * @param data [Object] object whit data to convert from ;)
     */
    System.prototype.convert_lang_string = function(string,data){
        var lang = this.get_lang();

        if(typeof lang[string] === "undefined"){
            return "Unknown lang key: "+string;
        }

        var use = lang[string];

        for(var i in data){
         use = use.replace(new RegExp("%"+i),data[i]);
        }

        return use;
    };

    System.prototype.update_screen_lang = function(){
        J("all").run(function(){
            if(typeof this.attr("langString") !== "undefined" && typeof this.attr("langData") !== "undefined"){
                var l_string = system.get_lang()[this.attr("langString")];
                var l_data   = JSON.parse(this.attr("langData"));

                for(var i in l_data){
                    l_string = l_string.replace(new RegExp("%"+i,"g"),l_data[i]);
                }

                this.context(l_string);
            }else if(typeof this.attr("langTitle") !== "undefined" && typeof this.attr("langData") !== "undefined"){
                var l_string = system.get_lang()[this.attr("langTitle")];
                var l_data   = JSON.parse(this.attr("langData"));

                for(var i in l_data){
                    l_string = l_string.replace(new RegExp("%"+i,"g"),l_data[i]);
                }

                this.getObject().title = l_string;
            }
        });
    };

    System.prototype.start_channel = function(){
        return this.data['startChannel'];
    };

    System.prototype.stop_chat = function(){
        if(this.get_protokol() != "ajax"){
            return false;
        }
        clearTimeout(this.timer);
    };

    System.prototype.start_chat = function(isStartChannel){
       if(typeof isStartChannel === "undefined"){
            isStartChannel = false;
        }

        if(this.get_protokol() != "ajax"){
            return false;
        }

        if(!isStartChannel && this.timer === null){
            this.new_ajax_query({
                'message' : '/ping',
                'channel' : 'Bot'
            });
        }else{
            this.stop_chat();
        }

        this.get_object("status").okay();
        this.timer = setTimeout(function(){
            system.new_ajax_query(undefined,'server.php?userBlock=' + system.data.browserBlock+'&sort='+system.data.user.sort+"&li=" + system.get_last_id())
        },this.data.timer);
    };

    System.prototype.open_chat = function(){
        this.get_object("status").okay();
        this.getDom("main").css("backgroundImage","none");

        this.getDom("option").run(function(){
            if(this.attr("what") == system.option_name.smylie){
                this.context(system.return_smylie_dom());
                return;
            }
        });

        if(this.get_protokol() == "ajax"){
            this.start_chat(true);
        }
    };

    System.prototype.get_channel_botton_color = function(){
      return this.data.channelColor;
    };

    System.prototype.ping_socket = function(){
      setTimeout(function(){
          system.new_ajax_query({
              'message' : '/ping',
              'channel' : 'Bot'
          });

          system.ping_socket();
      },500000);
    };

    System.prototype.is_in_this_channel = function(name){
     if(this.chat_name == name){
         return true;
     }

        return false;
    };

    return System;

})();