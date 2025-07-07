var parser = require('./parser');
//var mm = require('./mm');

var routes = [
    {
        // match a request for the key 'greeting'
        route: "greeting",
        // respond with a PathValue with the value of 'Hello World.'
        get: () => ({ path: ["greeting"], value: "Hello World" }),
    },
    {
        route: "genrelist[{integers:indices}].name",
        get: () => ({ path: ["greeting"], value: "Hello World" }),
        call: () => ({ path: ["greeting"], value: "Hello World" }),
    }

];



var input = [
           ['videos', 1234, 'summary'],
           'videos[555].summary',
           {path: 'videos[444].summary', value: 5}
       ];//var rst = parseTree(routes);

//console.log(rst);
var route = parser('one["test", \'test2\'].oneMore', true);
//var route = parser("genrelist[{integers:indices}].name", true);
//var route = parser.fromPathsOrPathValues(input, true);
console.log(route);
