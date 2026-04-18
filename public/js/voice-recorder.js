/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	// The require scope
/******/ 	var __webpack_require__ = {};
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
/*!****************************************!*\
  !*** ./resources/js/voice-recorder.js ***!
  \****************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
function _typeof(o) {
  "@babel/helpers - typeof";

  return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) {
    return typeof o;
  } : function (o) {
    return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o;
  }, _typeof(o);
}
function _regenerator() {
  /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */var e,
    t,
    r = "function" == typeof Symbol ? Symbol : {},
    n = r.iterator || "@@iterator",
    o = r.toStringTag || "@@toStringTag";
  function i(r, n, o, i) {
    var c = n && n.prototype instanceof Generator ? n : Generator,
      u = Object.create(c.prototype);
    return _regeneratorDefine2(u, "_invoke", function (r, n, o) {
      var i,
        c,
        u,
        f = 0,
        p = o || [],
        y = !1,
        G = {
          p: 0,
          n: 0,
          v: e,
          a: d,
          f: d.bind(e, 4),
          d: function d(t, r) {
            return i = t, c = 0, u = e, G.n = r, a;
          }
        };
      function d(r, n) {
        for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) {
          var o,
            i = p[t],
            d = G.p,
            l = i[2];
          r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0));
        }
        if (o || r > 1) return a;
        throw y = !0, n;
      }
      return function (o, p, l) {
        if (f > 1) throw TypeError("Generator is already running");
        for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) {
          i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u);
          try {
            if (f = 2, i) {
              if (c || (o = "next"), t = i[o]) {
                if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object");
                if (!t.done) return t;
                u = t.value, c < 2 && (c = 0);
              } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1);
              i = e;
            } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break;
          } catch (t) {
            i = e, c = 1, u = t;
          } finally {
            f = 1;
          }
        }
        return {
          value: t,
          done: y
        };
      };
    }(r, o, i), !0), u;
  }
  var a = {};
  function Generator() {}
  function GeneratorFunction() {}
  function GeneratorFunctionPrototype() {}
  t = Object.getPrototypeOf;
  var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () {
      return this;
    }), t),
    u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c);
  function f(e) {
    return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e;
  }
  return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () {
    return this;
  }), _regeneratorDefine2(u, "toString", function () {
    return "[object Generator]";
  }), (_regenerator = function _regenerator() {
    return {
      w: i,
      m: f
    };
  })();
}
function _regeneratorDefine2(e, r, n, t) {
  var i = Object.defineProperty;
  try {
    i({}, "", {});
  } catch (e) {
    i = 0;
  }
  _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) {
    function o(r, n) {
      _regeneratorDefine2(e, r, function (e) {
        return this._invoke(r, n, e);
      });
    }
    r ? i ? i(e, r, {
      value: n,
      enumerable: !t,
      configurable: !t,
      writable: !t
    }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2));
  }, _regeneratorDefine2(e, r, n, t);
}
function asyncGeneratorStep(n, t, e, r, o, a, c) {
  try {
    var i = n[a](c),
      u = i.value;
  } catch (n) {
    return void e(n);
  }
  i.done ? t(u) : Promise.resolve(u).then(r, o);
}
function _asyncToGenerator(n) {
  return function () {
    var t = this,
      e = arguments;
    return new Promise(function (r, o) {
      var a = n.apply(t, e);
      function _next(n) {
        asyncGeneratorStep(a, r, o, _next, _throw, "next", n);
      }
      function _throw(n) {
        asyncGeneratorStep(a, r, o, _next, _throw, "throw", n);
      }
      _next(void 0);
    });
  };
}
function _classCallCheck(a, n) {
  if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function");
}
function _defineProperties(e, r) {
  for (var t = 0; t < r.length; t++) {
    var o = r[t];
    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o);
  }
}
function _createClass(e, r, t) {
  return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", {
    writable: !1
  }), e;
}
function _toPropertyKey(t) {
  var i = _toPrimitive(t, "string");
  return "symbol" == _typeof(i) ? i : i + "";
}
function _toPrimitive(t, r) {
  if ("object" != _typeof(t) || !t) return t;
  var e = t[Symbol.toPrimitive];
  if (void 0 !== e) {
    var i = e.call(t, r || "default");
    if ("object" != _typeof(i)) return i;
    throw new TypeError("@@toPrimitive must return a primitive value.");
  }
  return ("string" === r ? String : Number)(t);
}
var VoiceRecorder = /*#__PURE__*/function () {
  function VoiceRecorder() {
    _classCallCheck(this, VoiceRecorder);
    this.mediaRecorder = null;
    this.audioChunks = [];
    this.isRecording = false;
    this.stream = null;
    this.recordingStartTime = null;
    this.recordingDuration = 0;
    this.maxDuration = 300; // 5 minutes in seconds
    this.timer = null;
  }
  return _createClass(VoiceRecorder, [{
    key: "initialize",
    value: function () {
      var _initialize = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee() {
        var _this = this;
        var constraints, _t, _t2;
        return _regenerator().w(function (_context) {
          while (1) switch (_context.p = _context.n) {
            case 0:
              _context.p = 0;
              if (VoiceRecorder.isSupported()) {
                _context.n = 1;
                break;
              }
              throw new Error(VoiceRecorder.getUnsupportedReason());
            case 1:
              // Request microphone with fallback constraints
              constraints = {
                audio: {
                  echoCancellation: true,
                  noiseSuppression: true,
                  autoGainControl: true,
                  sampleRate: 44100,
                  sampleSize: 16,
                  channelCount: 1
                }
              };
              _context.p = 2;
              _context.n = 3;
              return navigator.mediaDevices.getUserMedia(constraints);
            case 3:
              this.stream = _context.v;
              _context.n = 6;
              break;
            case 4:
              _context.p = 4;
              _t = _context.v;
              // Try with basic constraints if advanced features fail
              console.warn('Advanced audio constraints failed, trying basic:', _t);
              constraints = {
                audio: true
              };
              _context.n = 5;
              return navigator.mediaDevices.getUserMedia(constraints);
            case 5:
              this.stream = _context.v;
            case 6:
              this.mediaRecorder = new MediaRecorder(this.stream, {
                mimeType: this.getSupportedMimeType()
              });
              this.mediaRecorder.ondataavailable = function (event) {
                if (event.data.size > 0) {
                  _this.audioChunks.push(event.data);
                }
              };
              this.mediaRecorder.onstop = function () {
                _this.handleRecordingStop();
              };
              return _context.a(2, true);
            case 7:
              _context.p = 7;
              _t2 = _context.v;
              console.error('Failed to initialize voice recorder:', _t2);
              throw _t2;
            case 8:
              return _context.a(2);
          }
        }, _callee, this, [[2, 4], [0, 7]]);
      }));
      function initialize() {
        return _initialize.apply(this, arguments);
      }
      return initialize;
    }()
  }, {
    key: "getSupportedMimeType",
    value: function getSupportedMimeType() {
      var types = ['audio/mp4',
      // M4A - widely supported, pure audio
      'audio/mpeg',
      // MP3 - universal support
      'audio/ogg',
      // OGG - good browser support, pure audio
      'audio/wav',
      // WAV - uncompressed, pure audio
      'audio/webm;codecs=opus',
      // Fallback
      'audio/webm' // Last resort fallback
      ];
      for (var _i = 0, _types = types; _i < _types.length; _i++) {
        var type = _types[_i];
        if (MediaRecorder.isTypeSupported(type)) {
          return type;
        }
      }
      return '';
    }
  }, {
    key: "startRecording",
    value: function () {
      var _startRecording = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee2() {
        var _this2 = this;
        var _t3;
        return _regenerator().w(function (_context2) {
          while (1) switch (_context2.p = _context2.n) {
            case 0:
              if (!this.isRecording) {
                _context2.n = 1;
                break;
              }
              return _context2.a(2, false);
            case 1:
              _context2.p = 1;
              if (this.mediaRecorder) {
                _context2.n = 2;
                break;
              }
              _context2.n = 2;
              return this.initialize();
            case 2:
              this.audioChunks = [];
              this.recordingStartTime = Date.now();
              this.recordingDuration = 0;
              this.isRecording = true;
              this.mediaRecorder.start(100); // Collect data every 100ms

              // Start timer
              this.timer = setInterval(function () {
                _this2.recordingDuration = Math.floor((Date.now() - _this2.recordingStartTime) / 1000);
                _this2.updateRecordingUI();

                // Auto-stop if max duration reached
                if (_this2.recordingDuration >= _this2.maxDuration) {
                  _this2.stopRecording();
                }
              }, 100);
              this.updateRecordingUI();
              return _context2.a(2, true);
            case 3:
              _context2.p = 3;
              _t3 = _context2.v;
              console.error('Failed to start recording:', _t3);
              this.isRecording = false;
              throw _t3;
            case 4:
              return _context2.a(2);
          }
        }, _callee2, this, [[1, 3]]);
      }));
      function startRecording() {
        return _startRecording.apply(this, arguments);
      }
      return startRecording;
    }()
  }, {
    key: "stopRecording",
    value: function stopRecording() {
      if (!this.isRecording || !this.mediaRecorder) {
        return false;
      }
      this.isRecording = false;
      this.mediaRecorder.stop();
      if (this.timer) {
        clearInterval(this.timer);
        this.timer = null;
      }
      return true;
    }
  }, {
    key: "cancelRecording",
    value: function cancelRecording() {
      if (this.isRecording) {
        this.isRecording = false;
        this.mediaRecorder.stop();
      }
      if (this.timer) {
        clearInterval(this.timer);
        this.timer = null;
      }
      this.audioChunks = [];
      this.recordingDuration = 0;
      this.updateRecordingUI();
    }
  }, {
    key: "handleRecordingStop",
    value: function handleRecordingStop() {
      if (this.audioChunks.length === 0) {
        console.warn('No audio data recorded');
        return;
      }
      var audioBlob = new Blob(this.audioChunks, {
        type: this.getSupportedMimeType()
      });

      // Minimum recording duration (1 second)
      if (this.recordingDuration < 1) {
        console.warn('Recording too short');
        this.updateRecordingUI();
        return;
      }

      // Determine file extension based on MIME type
      var mimeType = this.getSupportedMimeType();
      var extension = 'audio';
      if (mimeType.includes('mp4')) extension = 'm4a';else if (mimeType.includes('mpeg')) extension = 'mp3';else if (mimeType.includes('ogg')) extension = 'ogg';else if (mimeType.includes('wav')) extension = 'wav';else if (mimeType.includes('webm')) extension = 'webm';

      // Create audio file with proper extension
      var audioFile = new File([audioBlob], "voice_message_".concat(Date.now(), ".").concat(extension), {
        type: audioBlob.type
      });
      this.onRecordingComplete(audioFile, this.recordingDuration);
      this.updateRecordingUI();
    }
  }, {
    key: "onRecordingComplete",
    value: function onRecordingComplete(audioFile, duration) {
      // This will be overridden by the implementation
      console.log('Recording complete:', audioFile, duration);
    }
  }, {
    key: "updateRecordingUI",
    value: function updateRecordingUI() {
      // Update recording status in UI
      var event = new CustomEvent('voiceRecordingUpdate', {
        detail: {
          isRecording: this.isRecording,
          duration: this.recordingDuration,
          maxDuration: this.maxDuration
        }
      });
      document.dispatchEvent(event);
    }
  }, {
    key: "formatDuration",
    value: function formatDuration(seconds) {
      var minutes = Math.floor(seconds / 60);
      var remainingSeconds = seconds % 60;
      return "".concat(minutes, ":").concat(remainingSeconds.toString().padStart(2, '0'));
    }
  }, {
    key: "destroy",
    value: function destroy() {
      if (this.isRecording) {
        this.stopRecording();
      }
      if (this.stream) {
        this.stream.getTracks().forEach(function (track) {
          return track.stop();
        });
        this.stream = null;
      }
      if (this.timer) {
        clearInterval(this.timer);
        this.timer = null;
      }
      this.mediaRecorder = null;
      this.audioChunks = [];
    }
  }], [{
    key: "isSupported",
    value: function isSupported() {
      // Check for basic APIs only - don't check permissions here
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        return false;
      }
      if (!window.MediaRecorder) {
        return false;
      }

      // Check if running on HTTPS or localhost (required for microphone access)
      if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
        return false;
      }

      // Check if at least one audio mime type is supported
      var supportedTypes = ['audio/mp4',
      // M4A - widely supported, pure audio
      'audio/mpeg',
      // MP3 - universal support
      'audio/ogg',
      // OGG - good browser support, pure audio
      'audio/wav',
      // WAV - uncompressed, pure audio
      'audio/webm;codecs=opus',
      // Fallback
      'audio/webm' // Last resort fallback
      ];
      return supportedTypes.some(function (type) {
        return MediaRecorder.isTypeSupported(type);
      });
    }
  }, {
    key: "getUnsupportedReason",
    value: function getUnsupportedReason() {
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        return 'Your browser does not support microphone access. Please use a modern browser like Chrome, Firefox, Safari, or Edge.';
      }
      if (!window.MediaRecorder) {
        return 'Your browser does not support audio recording. Please update your browser to the latest version.';
      }
      if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
        return 'Voice recording requires a secure connection (HTTPS). Please access this site over HTTPS.';
      }
      var supportedTypes = ['audio/mp4',
      // M4A - widely supported, pure audio
      'audio/mpeg',
      // MP3 - universal support
      'audio/ogg',
      // OGG - good browser support, pure audio
      'audio/wav',
      // WAV - uncompressed, pure audio
      'audio/webm;codecs=opus',
      // Fallback
      'audio/webm' // Last resort fallback
      ];
      if (!supportedTypes.some(function (type) {
        return MediaRecorder.isTypeSupported(type);
      })) {
        return 'Your browser does not support any of the required audio formats for recording.';
      }
      return 'Voice recording is not supported for an unknown reason.';
    }
  }, {
    key: "checkPermission",
    value: function () {
      var _checkPermission = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee3() {
        var result, _t4;
        return _regenerator().w(function (_context3) {
          while (1) switch (_context3.p = _context3.n) {
            case 0:
              _context3.p = 0;
              if (navigator.permissions) {
                _context3.n = 1;
                break;
              }
              return _context3.a(2, 'prompt');
            case 1:
              _context3.n = 2;
              return navigator.permissions.query({
                name: 'microphone'
              });
            case 2:
              result = _context3.v;
              return _context3.a(2, result.state);
            case 3:
              _context3.p = 3;
              _t4 = _context3.v;
              return _context3.a(2, 'prompt');
          }
        }, _callee3, null, [[0, 3]]);
      }));
      function checkPermission() {
        return _checkPermission.apply(this, arguments);
      }
      return checkPermission;
    }()
  }]);
}(); // Export for use in other modules
window.VoiceRecorder = VoiceRecorder;
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (VoiceRecorder);
/******/ })()
;
//# sourceMappingURL=voice-recorder.js.map