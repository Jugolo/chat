var Status = (function(){
    function Status(sy){
        this.s = sy;
	}

    Status.prototype.updateSprog = function(s){
        this.s = s;
    };

    Status.prototype.call = function(){
        J("#statusIndikator").css("backgroundColor","#0066FF");
        J("#statusIndikator").getObject().title = this.s.onWork;
    };

    Status.prototype.error = function(){
        J("#statusIndikator").css("backgroundColor","red");
        J("#statusIndikator").getObject().title = this.s.onServerError;
    };

    Status.prototype.okay = function(){
        J("#statusIndikator").css("backgroundColor","green");
        J("#statusIndikator").getObject().title = this.s.onOkay;
    };

    return Status;
})();