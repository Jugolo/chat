var Sound = (function(){

    function Sound(obj){
        this.Obj = obj.getObject();
    };

    Sound.prototype.play = function(){
        this.Obj.play();
    };

    Sound.prototype.stop = function(){
        this.Obj.pause();
        this.Obj.currentTime = 0;
    };

    Sound.prototype.maxTime = function(sek){
        this.sek = sek;
        this.Obj.addEventListener("timeupdate",this.stopTime);
    };

    Sound.prototype.stopTime = function(){
        if(system.get_object("sound").sek < system.get_object("sound").Obj.currentTime.toFixed(3)){
            system.get_object("sound").stop();
        }
    };

    return Sound;
})();