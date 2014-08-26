var Command = (function(){

    Command.prototype.msg = null;

	function Command(msg){
        this.msg = system.trim(msg);
	}

	Command.prototype.isCommand = function(){
        return /^\//.test(this.msg);
    };

	Command.prototype.get = function(){
        var c = this.msg.split(" ")[0];
        return c.replace("/","");
    };

    Command.prototype.get_param = function(){
        if(/^\/([a-zA-Z]*?)\s(.*?)$/.test(this.msg)){
            return /^\/([a-zA-Z]*?)\s(.*?)$/.exec(this.msg);
        }else{
            return null;
        }
    };

    Command.prototype.isValid = function(){
        switch(this.get(this.msg)){
            case 'join':
            case 'lukPriv':
            case 'msg':
            case 'nick':
            case 'config':
            case 'getOnline':
            case 'title':
            case 'exit':
            case 'getLang':
            case 'kick':
            case 'leave':
            case 'bot':
            case 'ban':
            case 'unban':
            case 'getStatus':
            case 'update':
            case 'clear':
            case 'file':
                return true;
        }
        return false;
    };
    return Command;
})();