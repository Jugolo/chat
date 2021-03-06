var Jdata = (function () {
    function Jdata(name, type) {
        this.Name = name;
        this.Type = type;
        switch (this.Type) {
            case 'id':
                if (!this.doId()) {
                    return;
                }
                break;
            case 'object':
                this.Obj = this.Name;
                this.Name = 'none';
                break;
            case 'class':
                if(!this.doClass()){
                    return;
                }
                break;
            case 'all':
                this.Obj = document.all;
                break;
            case 'element':
                if(!this.doElement()){
                    return;
                }
                break;
            default:
                alert("Jdata error: unknown element!");
                break;
        }
    }

    Jdata.prototype.attr = function(name){
        if(this.Type == 'id' || this.Type == 'object'){
            if(typeof this.Obj.getAttribute(name) == 'object'){
                return null;
            }

            return this.Obj.getAttribute(name);
        }else{
            return null;
        }
    };

    Jdata.prototype.setID = function(nId){
        if(this.Type != 'id'){
            alert("setID only work whit id!");
            return;
        }

        this.Obj.id = nId;
    };

    Jdata.prototype.r = null;

    Jdata.prototype.run = function(func){
        if(this.Type != 'class' && this.Type != 'all' && this.Type != 'object'){
            alert("Run is only work whit class or command all");
            return;
        }

        for(var i=0;i<this.Obj.length;i++){
            var obj = new Jdata(this.Obj[i],"object");
            obj.r = func;
            obj.r(this.Obj[i]);
        }
    };

    Jdata.prototype.toggle = function(){
        if(this.Type != 'id' && this.Type != 'object'){
            alert("Not supported");
            return;
        }else if(this.Obj.nodeName.toLowerCase() != "div"){
            alert("Only div supported");
            return;
        }

        if(this.Obj.style.display == 'block'){
            this.hide();
        }else{
            this.show();
        }
    };

    Jdata.prototype.hide = function(){
        if(this.Type == 'id' || this.Type == 'object'){
            this.Obj.style.display = 'none';
        }else{
            alert("Not supportet");
        }
    };

    Jdata.prototype.show = function(){
        if(this.Type == 'id' || this.Type == 'object'){
            this.Obj.style.display = 'block';
        }else{
            alert("Not supportet");
        }
    };

    Jdata.prototype.context = function(context){
        if(typeof context == "undefined"){
            if(this.Type == 'id' || this.Type == 'object'){
                if(typeof this.Obj.nodeName !== "undefined" && (this.Obj.nodeName.toLowerCase() == 'input' || this.Obj.nodeName.toLowerCase() == 'textarea' || this.Obj.nodeName.toLowerCase() == 'select')){
                    return this.Obj.value;
                }else{
                    return this.Obj.innerHTML;
                }
            }
        }else{
            if(this.Type == 'id' || this.Type == 'object'){
                if(typeof this.Obj.nodeName !== "undefined" && (this.Obj.nodeName.toLowerCase() == 'input' || this.Obj.nodeName.toLowerCase() == 'textarea' || this.Obj.nodeName.toLowerCase() == 'select')){
                    this.Obj.value = context;
                }else{
                    return this.Obj.innerHTML = context;
                }
            }
        }
    };

    Jdata.prototype.getOptions = function(func){
        if(this.Type == "id" || this.Type == 'object'){
            if(typeof this.Obj === "undefined" || typeof this.Obj.nodeName === "undefined"){
                return;
            }

            if(this.Obj.nodeName.toLowerCase() != 'select'){
                alert("Only select work whit Jdata.getOptions!");
                return;
            }

            for(var i=0;i<this.Obj.length;i++){
                var obj = new Jdata(this.Obj,"object");
                obj.r = func;
                obj.r(this.Obj.options[i].value,this.Obj.options[i].text,this.Obj.options[i]);
            }
        }
    };

    Jdata.prototype.setOption = function(value,text,selected){
        if(typeof selected === "undefined"){
            selected = false;
        }

        if(typeof this.Obj === "undefined"){
            return;
        }

        var op = document.createElement("option");
        op.value = value;
        op.innerHTML = text;
        op.selected = selected;
        this.Obj.appendChild(op);
    };

    Jdata.prototype.addList = function(value){
        if(typeof this.Obj === "undefined" || this.Obj.nodeName.toLowerCase() != 'datalist'){
            return;
        }

        var op = document.createElement("option");
        op.value = value;
        this.Obj.appendChild(op);
    };

    Jdata.prototype.setImage = function(url){
        if(this.Type == 'id' || this.Type == 'object'){
            if(this.Obj.nodeName.toLowerCase() == 'img'){
                this.Obj.src = url;
            }else{
                this.Obj.style.backgroundImage = "url("+url+")";
            }
        }
    };

    Jdata.prototype.doClass = function(){
        if(!document.getElementsByClassName(this.Name)){
            alert("Unknown elemtn "+this.Name);
            return false;
        }

        this.Obj = document.getElementsByClassName(this.Name);
        return true;
    };

    Jdata.prototype.doId = function () {
        if (!document.getElementById(this.Name)) {
            alert("Unknown element: " + this.Name);
            return false;
        }

        this.Obj = document.getElementById(this.Name);
        return true;
    };

    Jdata.prototype.doElement = function(){
        if(!document.getElementsByTagName(this.Name)){
            alert("Unknown element: " + this.Name);
            return false;
        }

        this.Obj = document.getElementsByTagName(this.Name);
        return true;
    };

    Jdata.prototype.empty = function () {
        if (this.Type == "id" || this.Type == "object") {
            this.context("");
        }
    };

    Jdata.prototype.css = function (key, value) {
        if (this.Type == "id" || this.Type == "object") {
            this.Obj.style[key] = value;
        }else if(this.Type == "class"){
            this.run(function(){
                this.css(key,value);
            });
        }
    };

    Jdata.prototype.append = function (data, first) {
        if (typeof first === "undefined") { first = false; }
        if (this.Type == "id" || this.Type == "object" || this.Type == "element") {
            if (first) {
                this.Obj.innerHTML = data + this.Obj.innerHTML;
            } else {
                this.Obj.innerHTML += data;
            }
        }else{
            alert("Append dosent support type: "+this.Type);
        }
    };

    Jdata.prototype.remove = function(){
        if(this.Type == "id" || this.Type == "object"){
            this.Obj.parentNode.removeChild(this.Obj);
            return true;
        }else if(this.Type === "class"){
            var is_found = false;
            for(var i=0;i<this.Obj.length;i++){
                is_found = this.Obj[i].parentNode.removeChild(this.Obj[i]) !== null ? true : false;
            }
            return is_found;
        }
        return false;
    };

    Jdata.prototype.onChange = function(func){
        if(this.Type == "id" || this.Type == 'object'){
            Jdata.prototype.onChangeCall = func;
            this.Obj.onchange = this.onChangeCall;
        }else{
            alert("Onchange not allow on "+this.Type);
        }
    };

    Jdata.prototype.getObject = function(){
        return this.Obj;
    };

    Jdata.prototype.onClick = function(func){
      this.Obj.onclick = func;
    };

    return Jdata;
})();

function J(data,alertType) {
    if(typeof alertType === "undefined"){alertType = false;}
    if (/^#/.test(data)) {
        if(alertType){alert("Id");}
        return new Jdata(/^#(.*?)$/.exec(data)[1], "id");
    } else if (typeof data == "object") {
        if(alertType){alert("Object");}
        return new Jdata(data, "object");
    }else if(/^\./.test(data)){
        if(alertType){alert("Class");}
        return new Jdata(/^\.(.*?)$/.exec(data)[1],'class');
    }else if(data === 'all'){
        return new Jdata('all','all');
    }else if(/^[a-zA-Z]*?$/.test(data)){
        if(alertType){alert("Element");}
        return new Jdata(data,"element");
    }
}