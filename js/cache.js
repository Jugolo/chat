var Cache = (function(){

    Cache.prototype.cache = [];
    Cache.prototype.users = [];
    Cache.prototype.Main  = null;
    Cache.prototype.cCid  = [];

    function Cache(sys){
        this.Main = sys;
    }

    Cache.prototype.get_users_in_channel = function(name){
        return this.users[name];
    };

    Cache.prototype.get_message_in_channel = function(cid){
        if(typeof this.cache[cid] === "undefined"){
            alert(cid+" er tom");
            return [];
        }

        return this.cache[cid];
    };

    Cache.prototype.remove_user_from_cache = function(uid,channel,is_nick){
        if(typeof is_nick === "undefined"){
            is_nick = false;
        }

        if(typeof this.users[channel] === "undefined"){
            return false;
        }

        var c_users = this.users[channel];
        for(var i=0;i<c_users.length;i++){
            if(!is_nick && c_users[i]['uid'] == uid || is_nick && this.Main.trim(c_users[i]['nick']) == this.Main.trim(uid)){
                this.users[channel].splice(i,1);
                return true;
            }
        }

        return false;
    };

    Cache.prototype.empty_cache = function(name,cid){
        if(typeof this.users[name] !== "undefined"){
            this.users[name] = undefined;
        }

        if(typeof this.cache[cid] !== "undefined"){
            this.cache[cid] = undefined;
        }

    };

    Cache.prototype.add_users = function(nick,uid,channel){
        if(this.is_user_cached(nick,uid,channel)){
            return false;
        }

        if(typeof this.users[channel] === "undefined"){
            this.users[channel] = [];
        }

        this.users[channel][this.users[channel].length] = {
            'uid'     : uid,
            'nick'    : nick,
            'channel' : channel
        };

        return true;
    };

    Cache.prototype.is_user_cached = function(nick,uid,channel){

        if(typeof this.users[channel] === "undefined"){
            return false;
        }

        var c_user = this.users[channel];
        for(var i=0;i<c_user.length;i++){
            if(c_user[i]['uid'] == uid){
                return true;
            }
        }

        return false;
    };

    Cache.prototype.get_cid_from_name = function(name){
        if(this.cCid.lastIndexOf(name) !== -1){
            return this.cCid.lastIndexOf(name);
        }else{
            return 0;
        }
    }

    Cache.prototype.add = function(cid,obj){
        if(typeof this.cache[cid] === "undefined"){
            this.cache[cid] = [];
            this.cCid[cid] = obj['channel'];
        }
        this.cache[cid][this.cache[cid].length] =  obj;
    };

    return Cache;
})();