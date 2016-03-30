/*
    copyright (c) 2015 MEGA-NET.RU 
    Authors: Nikolaev Dmitry <dn@mega-net.ru>, Panfilov Alexey <ap@mega-net.ru>
*/

"use strict";
var socket;

function ws_Conn( options ){
    if( typeof options === 'undefined' ){
	options = { };
    }
    var url = options.url || null;
    var debug = false;
    
    if( typeof Date.defineFormat === 'function' ){
	Date.defineFormat( 'logtime', '[%d.%m.%Y %T.%L]: ' );
        debug = options.debug || false;
    }else{
	console.log( 'Can\'t find MooTools.More.Date.defineFormat!' );
    }

    var reconnect =  {
	timeout: 5,
	state: true,
    };

    var events = {
	create: [],
	open: [],
	message: [],
	error: [],
	close: [],
	destroy: [],
    };

    
    function ws_debug( ){
	if( debug ){
	    console.log( new Date( ).format( 'logtime' ) );
	    $('wsDebug').set('html',new Date( ).format( 'logtime' ) );
	    Object.each( arguments, function( v, k ){
		console.log( ' ', v );
		if( ( typeof v === 'object' ) ){
		    Object.each( v, function( v2, k2 ){
			$('wsDebug').appendHTML( v2 );
		    } );
		}else{
		    $('wsDebug').appendHTML( v );
		}
	    } );
	}
    }
    
    function ws_process_events( event, data ){
	Array.each( events[event], function( v, k ){
	    if( typeof v === 'function' ){
		try{
		    ws_debug( 'Found function at index [' + k + '] for [' + event + '], try call ' + ( v.name ? v.name + '()' : 'it' ) + '...' );
		    v( data );
		}
		catch( e ){
		    ws_debug( 'Error occurs during exec user\'s function ===> ', e, ', data: ', data );
		}
	    }
	} );
    }

    var ws = {
	set debug ( value ){
	    if( typeof Date.defineFormat === 'function' ){
		debug = value;
		return true;
	    }
	    return false;
	},

	get debug ( ){
	    return debug;
	},

	get readyState ( ){
	    return socket.readyState;
	},

	set reconnect ( value ){
	    ws_debug( 'Directly setting rejected, because it is object!' );
	    return false;
	},

	get reconnect ( ){
	    return reconnect;
	},

	getEvents: function( event ){
	    if( typeof events[event] === 'object' ){
		var response = [];
		Array.each( events[event], function( v, k ){
		    response.push( v.name || 'anonymous' );
		} );
		return response;
	    }
	    return false;
	},

	addEvent: function( event, func ){
	    if( typeof func === 'function' && typeof events[event] === 'object' ){
		events[event].push( func );
		return true;
	    }
	    return false;
	},

	removeEvent: function( event, func ){
	    if( typeof func === 'function' && func.name && typeof events[event] === 'object' ){
		var index = events[event].indexOf( func );
		if( index !== -1 ){
		    events[event].splice( index, 1 );
		    return true;
		}
	    }
	    return false;
	},

	removeEvents: function( event ){
	    if( typeof events[event] === 'object' ){
		events[event] = [];
		return true;
	    }
	    return false;
	},

	send: function( data ){
	    if( typeof socket === 'object' && typeof socket.close === 'function' ){
		if( socket.readyState === 1 ){
		    return socket.send( data );
		}else{
		    ws_debug( 'Can\'t send, check WS status...', socket );
		}
	    }
	    return false;
	},

	close: function( ){
	    if( typeof socket === 'object' && typeof socket.close === 'function' ){
		return socket.close( );
	    }else{
		return false;
	    }
	},
	
	start: function( ){
	    if( reconnect.state !== true ){
		WS( );
	    }
	}
    }

    function ws_create( ){
	ws_debug( 'Connection created. Try to connect to '+ url +'...' );
	$('wsConn').set("html","Connecting to WS server...");
	ws_process_events( 'create' );
    }

    function ws_open( ){
	ws_debug( 'Connected ( state ' + socket.readyState + ' )' );
	$('wsConn').set("html",'Connected to WS ( state ' + socket.readyState + ' )' );
	$('mConnected').setStyles({ 'display': 'block', 'visibility': 'visible' } );
	ws_process_events( 'open', arguments );
    }

    function ws_message( msg ){
	try {
	    var data = JSON.parse( msg.data );
	    ws_debug( data );
	    if ( data.type !== undefined ){
		ws_debug( "WS recv data: ")
		ws_debug( data );
		if( typeof window[data.type] === 'function' ){
		    ws_debug( 'Exec func ' + data.type );
		    window[data.type]( data );
		    if( typeof window[( data.type + '_extended' )] === 'function' ){
			window[( data.type + '_extended' )]( data );
		    }
		}
	    }
	}
	catch( e ){
	    ws_debug( 'Error! ===>', e, ', data: ', msg );
	}
	ws_process_events( 'message', msg );
    }

    function ws_error( e ){
	ws_debug( 'Error occured. Details: "', e );
	ws_process_events( 'error', e );
    }

    function ws_close( ){
	if( socket.readyState === 2 ){
	    ws_debug( 'Closing... The connection is going throught the closing handshake ( state ' + socket.readyState + ' )' );
	    $('wsConn').set("html",'Closing WS connect ( state ' + socket.readyState + ' )' );
	}else if( socket.readyState === 3 ){
	    ws_debug( 'Connection closed... The connection has been closed or could not be opened ( state ' + socket.readyState + ' )' );
	    $('wsConn').set("html",'Connection to WS closed ( state ' + socket.readyState + ' )' );
	}else{
	    ws_debug( 'Connection closed... ( unhandled state ' + socket.readyState + ' )' );
	    $('wsConn').set("html",'WS connection closed... ( unhandled state ' + socket.readyState + ' )' );
	}
	if( reconnect.state === true ){
	    ws_debug( 'Sleep for ' + reconnect.timeout + ' sec and try to reconnect...' );
	    $('wsConn').set("html",'WS connection closed. Reconnect after ' + reconnect.timeout + ' sec' );
	    $('mConnected').setStyles( { 'display': 'none', 'visibility': 'hidden' } );
	    setTimeout( WS, reconnect.timeout * 1000 );
	}
	ws_process_events( 'close', arguments );
    }

    function ws_destroy( ){
	ws_process_events( 'destroy', arguments );
    }

    function WS( ){
	var tmp;
	try {
	    if( typeof socket === 'object' && socket.readyState === 1 ){
		tmp = reconnect.state;
		reconnect.state = false;
		ws.close( );
		reconnect.state = tmp;
    	    }
    	    ws_create( );
	    socket = new WebSocket( url );
	    socket.onopen = ws_open;
	    socket.onmessage = ws_message;
	    socket.onclose = ws_close;
	    socket.onerror = ws_error;
	}
	catch( e ) {
	    if( typeof reconnect  === 'object' && reconnect.state === true ){
		setTimeout( WS, reconnect.timeout * 1000 );
	    }
	    ws_error( e );
	}
    }

    window.addEvent( 'beforeunload', function( ){
	if( typeof socket  === 'object' && socket.readyState === 1 ){
	    reconnect.state = false;
	    ws.removeEvents( 'close' ); // wipe list of functions, fired on 'close' event
	    ws_process_events( 'destroy' );
	    socket.close( );
	}
    } );
    
    WS( );
    
    return ws;
}

/* END of WS CLIENT */