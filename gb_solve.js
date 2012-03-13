// Copyright (c) Tien Le, Wu-chang Feng, Ed Kaiser 2007-2011
// Portland State University
// The PoW validation function.

// The Solver
Number.prototype.unsign = function()// convert to unsign number
{
  if (this < 0){
    return (0x100000000+this) // max 32bit int number in hex?
  } else {
    return this;
  }
}

Number.prototype.pack = function() { // convert Unicode value to character
    // shift right 24 bit & 255
  return (String.fromCharCode((this>>>24) & 0xff) + String.fromCharCode((this >>> 16)&0xff) +
         String.fromCharCode((this>>> 8) & 0xff) + String.fromCharCode( this & 0xff));
}

Array.prototype.hexify = function () { // convert from decimal to hex
  var hex = "";
  var chunk = "";
  for(var t=0; t<this.length; t++) {
    chunk = this[t].toString(16);// array element to string
    while (chunk.length < 8) chunk = "0"+chunk;// padding ?
    hex += chunk;
  }
  return hex;
}

Array.prototype.pack = function () {// simple packing
  var packed="";
  for(var t=0; t<this.length; t++) {
    packed += this[t].pack();
  }
  return packed;
}

String.prototype.pad = function() {
  var s = new String();
  s += this + "\x80";// euro symbol ?
  for(var i=0;i<(64 - (this.length+5)%64);i++){ // ?
    s += "\x00";// padding with NULL chars
  }
  s += (this.length*8).pack();
  return s;
}

String.prototype.unpack = function() { //? algorithm
  var words = new Array(this.length/4);
  for(var j=0; j < this.length/4; j++){
    words[j] = ((this.charCodeAt(4*j)<<24)
             | (this.charCodeAt(4*j+1)<<16)
             | (this.charCodeAt(4*j+2)<<8)
             |  this.charCodeAt(4*j+3)).unsign();
  }
  return words;
}


String.prototype.unhexify = function() {
  var words = new Array(this.length/8);
  for(var j=0; j < this.length/8; j++){
    words[j] = parseInt("0x"+this.slice(8*j,8*j+8));
  }
  return words;
}


function SHA1() {};

SHA1.IV = [0x67452301, 0xEFCDAB89, 0x98BADCFE, 0x10325476, 0xC3D2E1F0];// random hex number

SHA1.circular_shift = function (bits,word) {
  return (((word) << (bits)) | ((word) >>> (32-(bits))));
}

SHA1.compress = function (block,chain) {

  var block_ = new Array();// new array
  for(var t=0; t<block.length; t++) { block_[t] = block[t]; } // replicate 1st argument

  var constants = [0x5A827999, 0x6ED9EBA1, 0x8F1BBCDC, 0xCA62C1D6]; // random hexa number
  var t;

  for (t=16; t < 80; t++) { // why runs from 16 to 80 ?
    block_[t] = SHA1.circular_shift(1,block_[t-3] ^ block_[t-8] ^ block_[t-14] ^ block_[t-16]);// word = 0(same) or 1(diff)
  }
// 5 array elements get from 2nd array (chain)
  var aa = chain[0];
  var bb = chain[1];
  var cc = chain[2];
  var dd = chain[3];
  var ee = chain[4];
  var tt;

  for(t=0; t < 20; t++){
    tt = 0xFFFFFFFF & (SHA1.circular_shift(5,aa) + ((bb & cc) | ((~bb) & dd)) + ee + block_[t] + constants[0]);
    ee = dd;
    dd = cc;
    cc = SHA1.circular_shift(30,bb);
    bb = aa;
    aa = tt;
  }

  for(t=20; t < 40; t++){
    tt = 0xFFFFFFFF & (SHA1.circular_shift(5,aa) + (bb ^ cc ^ dd) + ee + block_[t] + constants[1]);
    ee = dd;
    dd = cc;
    cc = SHA1.circular_shift(30,bb);
    bb = aa;
    aa = tt;
  }

  for(t=40; t<60; t++){
    tt = 0xFFFFFFFF & (SHA1.circular_shift(5,aa) + ((bb & cc) | (bb & dd) | (cc & dd)) + ee + block_[t] + constants[2]);
    ee = dd;
    dd = cc;
    cc = SHA1.circular_shift(30,bb);
    bb = aa;
    aa = tt;
  }

  for(t=60; t<80; t++){
    tt = 0xFFFFFFFF & (SHA1.circular_shift(5,aa) + (bb ^ cc ^ dd) + ee + block_[t] + constants[3]);
    ee = dd;
    dd = cc;
    cc = SHA1.circular_shift(30,bb);
    bb = aa;
    aa = tt;
  }

  return [(0xFFFFFFFF & (chain[0] + aa)).unsign(),
          (0xFFFFFFFF & (chain[1] + bb)).unsign(),
          (0xFFFFFFFF & (chain[2] + cc)).unsign(),
          (0xFFFFFFFF & (chain[3] + dd)).unsign(),
          (0xFFFFFFFF & (chain[4] + ee)).unsign()];
}

SHA1.hexdigest = function (message) {

  var digest="";// redundant 

  message = message.pad();

  words = message.unpack();  //.unpack();

  var chain = SHA1.IV;
  for(var t=0; t<(words.length/16); t++) {
    chain = SHA1.compress(words.slice(t*16,t*16+16),chain);
  }
  return chain.hexify();
}

function gb_Valid(output, D) { //check valid ?
   if (D <= 1) return 1;
   return (output[4] % D) == 0 ? 1 : 0;
}

var default_Nc = 0;
var default_Dc = 0;


function gb_ShowSplash(tag) { // running...progress-bar
   document.getElementById('gb-progress').style.visibility = 'visible';
 
   var width = (1 - Math.pow((1 - (1 / tag.Dc)), tag.A)) * 100;
   document.getElementById('gb-progress-bar').style.width = width + '%';
}


// A simple PoW Solution algorithm.
function gb_Solve(tag) {
   // Don't solve a second time.
   if (tag.solved == true) {
      return;
   }
   tag.solved = false;

   // Get the variables.
   if (tag.Nc == undefined) { // can't get Nc from server
      tag.Nc = default_Nc;
      if (navigator.appName != "Microsoft Internet Explorer" && tag.hasAttribute('Nc')) { 
         var t = tag.getAttribute('Nc');
         tag.Nc = parseInt(t, 16);
      }
   } else if (typeof(tag.Nc) == "string") { // IE -> just simply convert from string to int 16 bit
       tag.Nc = parseInt(tag.Nc, 16);
   }
   if (tag.Dc == undefined) { // can't get Dc from server
      tag.Dc = default_Dc;
      if (navigator.appName != "Microsoft Internet Explorer" && tag.hasAttribute('Dc')) { 
         var t = tag.getAttribute('Dc');
         tag.Dc = parseInt(t, 16);
      }
   } else if (typeof(tag.Dc) == "string") { // IE -> just simply convert from string to int 16 bit
     tag.Dc = parseInt(tag.Dc, 16);
   }
   if (tag.A == undefined) tag.A = 0;

   // Create the initial input. // is there any rule?
   if (tag.Dc > 1) { // get Dc from server
      var input = Array(16);
      input[0] = parseInt(tag.Nc);
      input[1] = parseInt(tag.Dc);
      input[2] = parseInt(tag.A);
      input[3] = 0x80000000;

      for (var i = 4; i < 15; i++) input[i] = 0;// from 4 to 14 = 0
      input[15] = 0x00000060; //last input = 96
      // Search for an answer.
      var output = SHA1.compress(input,SHA1.IV);//block input & const chain
      var timeout = (new Date()).getTime() + 9;
      while (!gb_Valid(output, tag.Dc)) {
         tag.A = ++input[2];
         output = SHA1.compress(input,SHA1.IV);
         if ((new Date()).getTime() > timeout) {
            if (navigator.appName == "Microsoft Internet Explorer") { 
               setTimeout(function() { gb_Solve(tag); }, 1);
            } else {
               setTimeout(gb_Solve, 1, tag);
            }
            return;
         }
      }
   }

  // Update the tag to indicate success and POST the form.
  tag.solved = true;
  // use jquery to get the value
  if(tag.onsolved) {
    tag.onsolved(tag.A);
  }
  else {
    $('#answer').val(tag.A);
    $('#do').val('submit');
    document.forms[1].submit();
  }
}

// get json back from preview.php (server)
function ajax()
{
    $.post("preview.php",$("#comment-form").serialize(), function(result)
    {
        var result = $.parseJSON(result);
        if(result.status == 'error')
        {
            alert(result.message);
        }
        else // solve the puzzle with Dc and Nc
        {
            gb_Solve(result);
        }
    });
}



// Solution progress screen that starts hidden.
document.writeln('<div id="gb-progress" style="visibility:hidden; position:absolute; left:0px; top:0px; width:100%; height:100%; z-index:200; opacity:0.85; background-color:#AAAAAA">\
                     <div style="position:absolute; top:50%; width:100%; font-family:verdana; font-weight:bold; color:#0000CC" align="center">\
                        <div style="position:relative; top:-20px; font-size:20px">\
                           Solving the Guestbook Proof-of-Work challenge:\
                        </div>\
                        <div style="position:relative; top:10px; width:300px; height:30px; border-style:solid; border-width:3px; border-color:#0000CC" align="left">\
                           <div id="gb-progress-bar" style="position:relative; left:0px; top:0px; width:0%; height:100%; background-color:#0000FF">\
                           </div>\
                        </div>\
                     </div>\
                  </div>');
