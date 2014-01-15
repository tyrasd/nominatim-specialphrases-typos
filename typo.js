var fs = require("fs");
var sqlite3 = require("sqlite3").verbose();
var _ = require("lodash");

var db = new sqlite3.Database("./taginfo-master.db", function(err) {
  if (err) console.log(err);
});

var f = fs.readFileSync("alltags.csv","utf-8");

var m = f.split("\n");

var tags = [];
m.forEach(function(s) {
  s = s.split("\t");
  if (s.length < 2) return;
  tags.push({key:s[0], val:s[1]});
});
tags = _.uniq(tags, function(a) {return a.key+a.val});



db.serialize(function() {
  db.exec("ATTACH DATABASE './taginfo-wiki.db' AS wiki;", function(err) {
    if (err) console.log(err);
  });
  tags.forEach(function(tag) {
    if (tag.key === "zoom")
      return;
    db.get("select count(*) as cnt from ("+
             "select key,value from interesting_tags where key=$key and value=$value union "+
             "select key,value from suggestions where key=$key and value=$value union "+
             "select key,value from wiki.wikipages_tags where key=$key and value=$value"+
           ")",
           {$key:tag.key, $value:tag.val}, 
      function(err,row) {
        if (err) console.log(err);
        if (row.cnt===0)
          if (tag.val.match(/^INT/) !== null)
            console.log("INT\t", tag);
          else
            console.log("???\t", "["+tag.key+"="+tag.val+"](http://taginfo.openstreetmap.org/tags/"+encodeURIComponent(tag.key)+"="+encodeURIComponent(tag.val)+")");
        else
          console.log("OK\t", tag);
      });
  });
});
