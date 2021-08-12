const glob = require("glob")
const async = require("async")
const fs = require("fs")

const files = glob.sync("**/*.*")
console.log("Total Files", files.length)

let curr = 0;
let total = files.length;

async.each(files, (file, callback) => {
let stat = fs.statSync(file)
if(!stat.isFile()) {
curr++;
callback()
return;
}

let content = fs.readFileSync(file, "utf-8")
	.split("Kriegerhost")
	.join("Kriegerhost")
	.split("kriegerhost")
	.join("kriegerhost")
	.split("krieger.host")
	.join("krieger.host")
fs.writeFileSync(file, content)
callback()
curr++
console.log(curr, "/", total)
}, err => console.log("Done!", err))
