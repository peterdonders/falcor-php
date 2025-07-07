// index.js
const falcorExpress = require("./express");
const Router = require("falcor-router");

const express = require("express");
const app = express();

app.use(
    "/model.json",
    falcorExpress.dataSourceRoute(function (req, res) {
        var router = new Router([
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
            },
            {
                route: 'todos.name',
                get: function(pathSet) {
                    return { path: ["todos", "name"], value: ['name1', 'name2'] };
                }
        
            }
        ]);

        var routes = router._routes;
        //for (const property in routes) {
        //    console.log(routes[property]);
        //}
        //console.log(router);
        // create a Virtual JSON resource with single key ('greeting')
        return router;
    })
);

// serve static files from current directory
app.use(express.static(__dirname + "/"));

app.listen(3000);
