#!/usr/bin/env node

var myOptions = require('./ws_server.options');
var ARI_HOST = myOptions.ari_host;
var ARI_PORT = myOptions.ari_port;
var API_KEY = myOptions.ari_user+':'+myOptions.ari_pass;
var APP_NAME = myOptions.application;
var MOH = myOptions.moh_class;
var BRIDGE_ID = '1449555469';
var BRIDGE_NAME = 'conference';
var WS_PORT = myOptions.ws_port;

var WebSocket = require('ws');
var WebSocketServer = require('ws').Server;
var request = require('request');

var ws = new WebSocket( 'ws://' + ARI_HOST + ':' + ARI_PORT + '/ari/events?app=' + APP_NAME + '&api_key=' + API_KEY );
var wss; 
var moh_chan;

ws.on('open', function open() {
    console.log( 'Connected to ARI' );
    request.post(
	'http://' + ARI_HOST + ':' + ARI_PORT + '/ari/bridges/' + BRIDGE_ID,
	{
	    form: {
		type: 'mixing',
		name: BRIDGE_NAME,
		'api_key': API_KEY,
	    }
	},
	function (error, response, body) {
    	    if (!error && response.statusCode == 200) {
//        	console.log(body)
    	    }
    	    console.log( 'Init/refresh confBridge done' );
	    init( );
	}
    );
});

ws.on('message', function(d, flags) {
    var data = JSON.parse( d );
//    console.log( 'ARI: ', d );
    console.log( data.type );
    if(
	( data.type && data.type == 'StasisStart' ) && 
	( data.application && data.application == APP_NAME ) && 
	( data.channel && typeof data.channel == 'object' && data.channel.id )
    ){
	request.post(
	    'http://' + ARI_HOST + ':' + ARI_PORT + '/ari/bridges/' + BRIDGE_ID + '/addChannel',
	    {
		form: {
		    channel: data.channel.id,
		    'api_key': API_KEY,
		}
	    },
	    function (error, response, body) {
    		if ( response.statusCode != 200) {
        		console.log( 'stasis start. WS on message: ', data.type, ': ', response.statusCode );
    		}
		moh_manage( data );
	    }
	);
    }else if(
	( data.type && data.type == 'StasisEnd' ) && 
	( data.application && data.application == APP_NAME ) && 
	( data.channel && typeof data.channel == 'object' && data.channel.id )
    ){
	request.del(
	    'http://' + ARI_HOST + ':' + ARI_PORT + '/ari/bridges/' + BRIDGE_ID + '/removeChannel',
	    {
		form: {
		    channel: data.channel.id,
		    'api_key': API_KEY,
		}
	    },
	    function (error, response, body) {
    		if ( response.statusCode != 200) {
        		console.log( 'stasis end. WS on message: ', data.type, ': ', response.statusCode );
    		}
		moh_manage( data );
	    }
	);
    }
    wss.broadcast( d );
  // flags.binary will be set if a binary data is received.
  // flags.masked will be set if the data was masked.
});

ws.on('error', function(e) {
    console.log('ARI connection error: ',e);
});

wss = new WebSocketServer({ port: myOptions.ws_port });

wss.broadcast = function broadcast(data) {
    wss.clients.forEach(function each(client) {
	if( client.readyState == 1 ){
	    client.send(data);
	}
    });
};

wss.on('connection', function connection(ws) {
    console.log('Web connected');
    init( ws );
    ws.on('message', function incoming(m) {
	var mess;
	console.log('received: %s', m);
	if( ( mess = JSON.parse( ( m ) ) ) !== false ){
	    console.log( 'MESS Decoded: ', mess );
	    if( mess.channel ){
		if( mess.mute === true ){
		    console.log('mute on' );
			    request.post(
				'http://' + ARI_HOST + ':' + ARI_PORT + '/ari/channels/' + mess.channel + '/mute',
				{
				    form: {
					direction: 'in',
				        'api_key': API_KEY,
				    }
				},
				function (error, response, body) {
    				    if ( response.statusCode != 204) {
        				console.log('Mute Resp: ', response.statusCode);
    				    }
				}
			    );
		}else if( mess.mute === false ){
		    console.log('mute off' );
			    request.del(
				'http://' + ARI_HOST + ':' + ARI_PORT + '/ari/channels/' + mess.channel + '/mute',
				{
				    form: {
				    direction: 'both',
				    'api_key': API_KEY,
				    },
				},
				function (error, response, body) {
    				    if (!error && response.statusCode != 204) {
        				console.log('UnMute chan Resp: ', response.statusCode);
    				    }
				}
			    );
		}else if( mess.kick === true ){
			    request.del(
				'http://' + ARI_HOST + ':' + ARI_PORT + '/ari/channels/' + mess.channel,
				{
				    form: {
				    'api_key': API_KEY,
				    },
				},
				function (error, response, body) {
    				    if (!error && response.statusCode != 204) {
        				console.log('Kick chan Resp: ', response.statusCode);
    				    }
				}
			    );

		}
	    }
	}
	ws.send( m );
	console.log('sended: %s', m);
    });

    ws.on('error', function( error ) {
	console.log('ERROR on socket occured:',error);
    });
});

function init( ws ){
    request.get(
	'http://' + ARI_HOST + ':' + ARI_PORT + '/ari/bridges/' + BRIDGE_ID,
	{
	    form: {
		'api_key': API_KEY,
	    }
	},
	function (error, response, b ) {
	    var data = { };
    	    console.log('Get bridges RESP: ', response.statusCode);
    	    if (!error && response.statusCode == 200) {
		if( ( body = JSON.parse( b ) ) !== false ){
		    if( body.channels ){
			body.channels.forEach(function each(chan_id) {
			    console.log( chan_id );
			    request.get(
				'http://' + ARI_HOST + ':' + ARI_PORT + '/ari/channels/' + chan_id,
				{
				    form: {
					'api_key': API_KEY,
				    }
				},
				function (error, response, b ) {
    				    console.log('Get chan info RESP: ', response.statusCode);
    				    if (!error && response.statusCode == 200) {
					if( ( body = JSON.parse( b ) ) !== false ){
					    if( body.caller ){
						console.log( body.caller );
						if( typeof ws == 'object' ){
						    ws.send( JSON.stringify( { type: 'StasisStart', channel: { id: chan_id, caller: body.caller } } ) );
    						}
					    }
					}
				    }
				}
			    );
			});
		    }
		}
	    }
	}
    );
}

function moh_manage( data ){
    if( data.type == 'StasisStart' || data.type == 'StasisEnd' ){
    request.get(
	'http://' + ARI_HOST + ':' + ARI_PORT + '/ari/bridges/' + BRIDGE_ID,
	{
	    form: {
		'api_key': API_KEY,
	    }
	},
	function (error, response, b ) {
	    var body;
    	    console.log('Get bridges RESP: ', response.statusCode);
    	    if (!error && response.statusCode == 200) {
		if( ( body = JSON.parse( b ) ) !== false ){
		    if( body.channels ){
			var l=0;
			body.channels.forEach(function each(client) {
			    l++;
			} );
			console.log( 'Channels: ', body.channels );
			console.log( 'LEN: ', l );
			if( l == 1 ){
			    moh_chan = body.channels[0];
			    console.log( 'Add MOH to ID: ', moh_chan );
			    request.post(
				'http://' + ARI_HOST + ':' + ARI_PORT + '/ari/channels/' + moh_chan + '/moh',
				{
				    form: {
					mohClass: MOH,
				        'api_key': API_KEY,
				    }
				},
				function (error, response, body) {
    				    if ( response.statusCode != 204) {
        				console.log('Add MOH Resp: ', response.statusCode);
    				    }
				}
			    );
			}else if( l == 2 ){
			    console.log( 'Del MOH from ID: ', moh_chan );
			    request.del(
				'http://' + ARI_HOST + ':' + ARI_PORT + '/ari/channels/' + moh_chan + '/moh',
				{
				    form: {
				    'api_key': API_KEY,
				    },
				},
				function (error, response, body) {
    				    if (!error && response.statusCode != 204) {
        				console.log('Remove MOH Resp: ', response.statusCode);
    				    }
				}
			    );
			}else{
			    console.log( l );
			}
		    }
		}
	    }
	}
    );
    }else{
	console.log('moh manag: MOH HZ: ', data.type);
    }
}
