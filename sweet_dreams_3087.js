const express = require('express');
// import express from 'express';
const app = express();
var fs = require('fs');
var mysql = require("mysql");

const notification_options = {
    priority: "high",
    timeToLive: 60 * 60 * 24
  };

// const options = {
//     key: fs.readFileSync('/home/server1appsstagi/ssl/keys/e89da_d8e8d_11cbb2fb9fdb01b1f53bdb3e7207c97e.key'),
//     cert: fs.readFileSync('/home/server1appsstagi/ssl/certs/server1_appsstaging_com_e89da_d8e8d_1727512625_3763d39843fc95911f9b061d3b5cbaa7.crt'),
// };

const options = {
    key: fs.readFileSync('/home/server1appsstagi/ssl/keys/c5d49_6fbbd_91de36adf15e0000b3dd2f05ba47f60e.key'),
    cert: fs.readFileSync('/home/server1appsstagi/ssl/certs/server1_appsstaging_com_c5d49_6fbbd_1732783117_da2d82bc3a3653f4ccc6ec69656e0673.crt'),
};

const server = require('https').createServer(options, app);
var io = require('socket.io')(server, {
    cors: {
        origin: "*",
        methods: ["GET", "POST","PATCH","DELETE"],
        credentials: false,
        transports: ['websocket', 'polling'],
        allowEIO3: true
    },
});

var con_mysql = mysql.createPool({
    host              :   'localhost',
    user              :   'server1appsstagi_sweet_dreams',
    password          :   ',{i@j1$8S1,8',
    database          :   'server1appsstagi_sweet_dreams',
    debug             :   true,
    charset:'utf8mb4'
});


var FCM = require('fcm-node');
var serverKey = 'AAAAc_22vhs:APA91bGuExfoSu3L5q3b73a2ieXiMiFEodz4FygFayAxnJAsLRUxDyMNcQlX0pNDuTGefOc3Y2TTMshHSpOJ2M4a5CD6B2DbA0fL-mdZaP1G04xDVGR_pATQFMFpDzpMfmt2vmgMqLdz';
var fcm = new FCM(serverKey);


// app.get('/', (req, res) => {
//   res.sendFile(__dirname + '/chat.html');
// });

io.on('connection', (socket) => {
  console.log('a user connected');

  console.log("JOINED ",socket.id);

     // GET MESSAGES EMIT
    socket.on('get_messages',function(object){

        var user_room = "user_"+object.sender_id;
        socket.join(user_room);

        get_messages(object,function(response){
            if(response){
                console.log("get_messages has been successfully executed...",response);
                console.log("sender is"+object.sender_id , "receiver is"+object.receiver_id);
                io.to(user_room).emit('response', {object_type:"get_messages", data:response});
            }else{
                console.log("get_messages has been failed...");
                io.to(user_room).emit('error', {object_type:"get_messages", message:"There is some problem in get_messages..."});
            }
        });
    });


    // SEND MESSAGE EMIT
    // SEND MESSAGE EMIT
    socket.on('send_message',function(object){
        /*console.log(object);*/
        var sender_room = "user_"+object.sender_id;
        var receiver_room = "user_"+object.receiver_id;
        socket.join(sender_room);
        socket.join(receiver_room);
        if(object.sender_id == object.receiver_id){
            console.log("sender and receiver cant be same");
        }else{
        send_message(object,function(response){
            if(response){
                console.log("send_message has been successfully executed...");
                console.log(response);
                // io.to(sender_room).to(receiver_room).emit('response', {object_type:"get_message", data:response});

                    if(object.type == 'text')
                    {
                        var msg = object.message;
                    }
                    else
                    {
                        var msg = "sent an image."
                    }

                    var user_name="";
                    var user_id="";
                    sender_user(object,function(response3){
                    user_name =response3[0].full_name;
                    user_id = response3[0].id;

                    console.log("push user: "+user_name+"***********");

                    //Push Notification
                    get_user_token(object,function(response2){
                        console.log("push notification");
                        console.log(response2);
                        if(response2.length>0){
                            const  registrationToken =response2[0].device_token;
                            var message = {
                                registration_ids: [registrationToken],
                                collapse_key: "your_collapse_key",
                                notification: {
                                    title: user_name+" send you a message.",
                                    body: msg,
                                    sender_id: ""+object.sender_id+"",
                                    sender_name:user_name,
                                },
                                data: {
                                    type: "chat",
                                    title: user_name+" send you a message.",
                                    body: msg,
                                    sender_id: ""+object.sender_id+"",
                                    sender_name:user_name,
                                }
                            };

                            console.log("&&&&&&&&&&&&&&&&&&&&&&",message);
                              //Push Notification
                                if(response2[0].notification === 1)
                                {
                                    fcm.send(message, async function(err, response_two){
                                    if (err) {
                                        console.log("Something has gone wrong!", err);
                                        // io.to(group_room).emit('response', { object_type: "get_message", data: response[0] });
                                        io.to(sender_room).to(receiver_room).emit('response', {object_type:"get_message", data:response});
                                    } else {
                                            // io.to(group_room).emit('response', { object_type: "get_message", data: response[0] });
                                            io.to(sender_room).to(receiver_room).emit('response', {object_type:"get_message", data:response});
                                            console.log("Successfully sent with response: ");
                                        }
                                    });
                                }
                                else
                                {
                                    console.log("Push Notification is off!");
                                    // io.to(group_room).emit('response', { object_type: "get_message", data: response[0] });
                                    io.to(sender_room).to(receiver_room).emit('response', {object_type:"get_message", data:response});
                                }

                        }

                    });

                    });

            }
            else
            {
                console.log("send_message has been failed...");
                io.to(sender_room).to(receiver_room).emit('error', {object_type:"get_message", message:"There is some problem in get_message..."});
            }

        });
        }
    });

});


// GET MESSAGES FUNCTION
var get_messages = function(object,callback){
    con_mysql.getConnection(function(error,connection){
        if(error){
            callback(false);
        }else{
            connection.query(`SELECT u.full_name, u.profile_image, c.*
                                FROM users AS u
                                JOIN chats AS c
                                ON u.id = c.sender_id
                                WHERE ((c.sender_id = '${object.sender_id}' AND c.receiver_id = '${object.receiver_id}')
                                OR (c.sender_id = '${object.receiver_id}' AND c.receiver_id = '${object.sender_id}')) AND c.delete_convo != '${object.sender_id}'ORDER BY c.id ASC`, function(error,data){


                connection.release();
                if(error){
                    callback(false);
                }else{
                    callback(data);
                }
            });
        }
    });
};



// VERIFY LIST FUNCTION
var verify_list = function(message,callback){
    con_mysql.getConnection(function(error,connection){
        if(error){
            callback(false);
        }else{
            connection.query(`SELECT * from conversations where (sender_id = ${message.sender_id} AND receiver_id = ${message.receiver_id} ) OR (receiver_id = ${message.sender_id} AND sender_id = ${message.receiver_id})  LIMIT 1 `, function(error,data){
                if(error){
                    callback(false);
                }else{
                        var today = new Date();
                        var date = today.getFullYear()+'-'+(today.getMonth()+1)+'-'+today.getDate();
                        var time = today.getHours() + ":" + today.getMinutes() + ":" + today.getSeconds();
                        var dateTime = date+' '+time;
                        console.log(dateTime);
                    if(data.length === 0){

                        connection.query(`INSERT INTO conversations (sender_id , receiver_id , type , last_message , created_at)
                        VALUES ('${message.sender_id}' , '${message.receiver_id}', '${message.type}' , '${message.message}' , '${dateTime}')`, function(error,data){
                            connection.release();
                            if(error){
                                callback(false);
                            }else{
                                callback(data.insertId);
                            }
                        });
                    }
                    else{
                        //counter code
                        // var dbcounter = data[0].counter;
                        // console.log(dbcounter);
                        // var counter = ++dbcounter;

                        connection.query(`UPDATE  conversations SET last_message= '${message.message}', type= '${message.type}', created_at = '${dateTime}' WHERE id = ${data[0].id}`, function(error,data){
                            connection.release();
                            if(error){
                                callback(false);
                            }
                        });
                        callback(data[0].id);
                    }
                }
            });
        }
    });
};

// SEND MESSAGE FUNCTION
var send_message = function(object,callback){
    con_mysql.getConnection(function(error,connection){
        if(error){
            callback(false);
        }else{

            verify_list(object,function(response){
                if(response){
                    // var new_message = mysql_real_escape_string (object.message);
                     console.log("insert into chats has been successfully executed...");
                     var today = new Date();
                        var date = today.getFullYear()+'-'+(today.getMonth()+1)+'-'+today.getDate();
                        var time = today.getHours() + ":" + today.getMinutes() + ":" + today.getSeconds();
                        var dateTime = date+' '+time;
                         console.log(dateTime);
                    connection.query(`INSERT INTO chats SET sender_id = '${object.sender_id}', receiver_id = '${object.receiver_id}', type = '${object.type}' ,message = '${object.message}' , conversation_id = ${response} , created_at = '${dateTime}'`, function(error,data){
                    //connection.query(`INSERT INTO chats SET sender_id = 1, receiver_id = 2, message = 'hello'`, function(error,data){
                        if(error){
                            console.log("error",error)
                            connection.release();
                            callback(false);

                        }else{

                            console.log("success")
                            // connection.release();


                            connection.query(`SELECT * FROM chat_deletes WHERE (user_id = '${object.sender_id}' AND receiver_id = '${object.receiver_id}')
                                OR (user_id = '${object.receiver_id}' AND receiver_id = '${object.sender_id}')`, function(error,data2){
                            if(error){
                                console.log("error",error)
                            }
                            else
                            {
                                console.log("**********************",data2);
                                if(data2.length > 0)
                                {
                                    connection.query(`DELETE FROM chat_deletes WHERE (user_id = '${object.sender_id}' AND receiver_id = '${object.receiver_id}')
                                        OR (user_id = '${object.receiver_id}' AND receiver_id = '${object.sender_id}')`, function(error,data){
                                    if(error){
                                        console.log("error",error)
                                    }
                                    });
                                }
                            }
                            });


                            // connection.query(`SELECT u.name, u.profile_image, c.*
                            //     FROM users AS u
                            //     JOIN chats AS c
                            //     ON u.id = c.receiver_id
                            //     WHERE c.id = '${data.insertId}'`, function(error,data_record){
                            //     connection.release();
                            //     if(error){
                            //         callback(false);
                            //     }else{
                            //         console.log(data_record);
                            //         callback(data_record);
                            //     }
                            // });




                            connection.query(`SELECT u.full_name, u.profile_image, c.*
                                FROM users AS u
                                JOIN chats AS c
                                ON u.id = c.sender_id
                                WHERE c.conversation_id = '${response}' AND c.delete_convo != '${object.sender_id}' ORDER BY c.id ASC`, function(error,data){
                                connection.release();
                                if(error){
                                    callback(false);
                                }else{
                                    //console.log(data);
                                    // const propertyNames = Object.keys(data);
                                    // console.log(propertyNames);
                                    callback(data);
                                }
                            });

                        }
                    });


                }
                else{
                    console.log("verify_list has been failed...");
                    callback(false);
                }

            });

        }
    });
};



//Push Notification

var get_user_token = function(object,callback){
    con_mysql.getConnection(function(error,connection){
        if(error){
            callback(false);
        }else{
            connection.query(`select * from users where id=${object.receiver_id}`, function(error,data){
                connection.release();
                if(error){
                    callback(error);
                }else{
                    callback(data);
                }
            });
        }
    });
};

var sender_user = function(object,callback){
    con_mysql.getConnection(function(error,connection){
        if(error){
            callback(false);
        }else{
            connection.query(`select * from users where id=${object.sender_id}`, function(error,data){
                connection.release();
                if(error){
                    callback(error);
                }else{
                    callback(data);
                }
            });
        }
    });
};

// var get_setting = function(object,callback){
//     con_mysql.getConnection(function(error,connection){
//         if(error){
//             callback(false);
//         }else{
//             connection.query(`select * from settings where user_id=${object.receiver_id}`, function(error,data){
//                 connection.release();
//                 if(error){
//                     callback(error);
//                 }else{
//                     callback(data);
//                 }
//             });
//         }
//     });
// };

function mysql_real_escape_string (str) {
    return str.replace(/[\0\x08\x09\x1a\n\r"'\\\%]/g, function (char) {
        switch (char) {
            case "\0":
                return "\\0";
            case "\x08":
                return "\\b";
            case "\x09":
                return "\\t";
            case "\x1a":
                return "\\z";
            case "\n":
                return "\\n";
            case "\r":
                return "\\r";
            case "\"":
            case "'":
            case "\\":
            case "%":
                return "\\"+char; // prepends a backslash to backslash, percent,
                                  // and double/single quotes
            default:
                return char;
        }
    });
}

server.listen(3087, () => {
  console.log('listening on *:3087');
});
