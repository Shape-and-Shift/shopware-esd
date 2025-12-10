import {
  __commonJS
} from "./chunk-BUSYA2B4.js";

// ../../../../../custom/plugins/shopware-esd/src/Resources/app/administration/node_modules/papaparse/papaparse.min.js
var require_papaparse_min = __commonJS({
  "../../../../../custom/plugins/shopware-esd/src/Resources/app/administration/node_modules/papaparse/papaparse.min.js"(exports, module) {
    !function(e, t) {
      "function" == typeof define && define.amd ? define([], t) : "object" == typeof module && "undefined" != typeof exports ? module.exports = t() : e.Papa = t();
    }(exports, function s() {
      "use strict";
      var f = "undefined" != typeof self ? self : "undefined" != typeof window ? window : void 0 !== f ? f : {};
      var n = !f.document && !!f.postMessage, o = f.IS_PAPA_WORKER || false, a = {}, u = 0, b = { parse: function(e, t) {
        var r2 = (t = t || {}).dynamicTyping || false;
        J(r2) && (t.dynamicTypingFunction = r2, r2 = {});
        if (t.dynamicTyping = r2, t.transform = !!J(t.transform) && t.transform, t.worker && b.WORKERS_SUPPORTED) {
          var i = function() {
            if (!b.WORKERS_SUPPORTED) return false;
            var e2 = (r3 = f.URL || f.webkitURL || null, i2 = s.toString(), b.BLOB_URL || (b.BLOB_URL = r3.createObjectURL(new Blob(["var global = (function() { if (typeof self !== 'undefined') { return self; } if (typeof window !== 'undefined') { return window; } if (typeof global !== 'undefined') { return global; } return {}; })(); global.IS_PAPA_WORKER=true; ", "(", i2, ")();"], { type: "text/javascript" })))), t2 = new f.Worker(e2);
            var r3, i2;
            return t2.onmessage = _, t2.id = u++, a[t2.id] = t2;
          }();
          return i.userStep = t.step, i.userChunk = t.chunk, i.userComplete = t.complete, i.userError = t.error, t.step = J(t.step), t.chunk = J(t.chunk), t.complete = J(t.complete), t.error = J(t.error), delete t.worker, void i.postMessage({ input: e, config: t, workerId: i.id });
        }
        var n2 = null;
        b.NODE_STREAM_INPUT, "string" == typeof e ? (e = function(e2) {
          if (65279 === e2.charCodeAt(0)) return e2.slice(1);
          return e2;
        }(e), n2 = t.download ? new l(t) : new p(t)) : true === e.readable && J(e.read) && J(e.on) ? n2 = new g(t) : (f.File && e instanceof File || e instanceof Object) && (n2 = new c(t));
        return n2.stream(e);
      }, unparse: function(e, t) {
        var n2 = false, _2 = true, m2 = ",", y2 = "\r\n", s2 = '"', a2 = s2 + s2, r2 = false, i = null, o2 = false;
        !function() {
          if ("object" != typeof t) return;
          "string" != typeof t.delimiter || b.BAD_DELIMITERS.filter(function(e2) {
            return -1 !== t.delimiter.indexOf(e2);
          }).length || (m2 = t.delimiter);
          ("boolean" == typeof t.quotes || "function" == typeof t.quotes || Array.isArray(t.quotes)) && (n2 = t.quotes);
          "boolean" != typeof t.skipEmptyLines && "string" != typeof t.skipEmptyLines || (r2 = t.skipEmptyLines);
          "string" == typeof t.newline && (y2 = t.newline);
          "string" == typeof t.quoteChar && (s2 = t.quoteChar);
          "boolean" == typeof t.header && (_2 = t.header);
          if (Array.isArray(t.columns)) {
            if (0 === t.columns.length) throw new Error("Option columns is empty");
            i = t.columns;
          }
          void 0 !== t.escapeChar && (a2 = t.escapeChar + s2);
          ("boolean" == typeof t.escapeFormulae || t.escapeFormulae instanceof RegExp) && (o2 = t.escapeFormulae instanceof RegExp ? t.escapeFormulae : /^[=+\-@\t\r].*$/);
        }();
        var u2 = new RegExp(Q(s2), "g");
        "string" == typeof e && (e = JSON.parse(e));
        if (Array.isArray(e)) {
          if (!e.length || Array.isArray(e[0])) return h2(null, e, r2);
          if ("object" == typeof e[0]) return h2(i || Object.keys(e[0]), e, r2);
        } else if ("object" == typeof e) return "string" == typeof e.data && (e.data = JSON.parse(e.data)), Array.isArray(e.data) && (e.fields || (e.fields = e.meta && e.meta.fields || i), e.fields || (e.fields = Array.isArray(e.data[0]) ? e.fields : "object" == typeof e.data[0] ? Object.keys(e.data[0]) : []), Array.isArray(e.data[0]) || "object" == typeof e.data[0] || (e.data = [e.data])), h2(e.fields || [], e.data || [], r2);
        throw new Error("Unable to serialize unrecognized input");
        function h2(e2, t2, r3) {
          var i2 = "";
          "string" == typeof e2 && (e2 = JSON.parse(e2)), "string" == typeof t2 && (t2 = JSON.parse(t2));
          var n3 = Array.isArray(e2) && 0 < e2.length, s3 = !Array.isArray(t2[0]);
          if (n3 && _2) {
            for (var a3 = 0; a3 < e2.length; a3++) 0 < a3 && (i2 += m2), i2 += v2(e2[a3], a3);
            0 < t2.length && (i2 += y2);
          }
          for (var o3 = 0; o3 < t2.length; o3++) {
            var u3 = n3 ? e2.length : t2[o3].length, h3 = false, f2 = n3 ? 0 === Object.keys(t2[o3]).length : 0 === t2[o3].length;
            if (r3 && !n3 && (h3 = "greedy" === r3 ? "" === t2[o3].join("").trim() : 1 === t2[o3].length && 0 === t2[o3][0].length), "greedy" === r3 && n3) {
              for (var d2 = [], l2 = 0; l2 < u3; l2++) {
                var c2 = s3 ? e2[l2] : l2;
                d2.push(t2[o3][c2]);
              }
              h3 = "" === d2.join("").trim();
            }
            if (!h3) {
              for (var p2 = 0; p2 < u3; p2++) {
                0 < p2 && !f2 && (i2 += m2);
                var g2 = n3 && s3 ? e2[p2] : p2;
                i2 += v2(t2[o3][g2], p2);
              }
              o3 < t2.length - 1 && (!r3 || 0 < u3 && !f2) && (i2 += y2);
            }
          }
          return i2;
        }
        function v2(e2, t2) {
          if (null == e2) return "";
          if (e2.constructor === Date) return JSON.stringify(e2).slice(1, 25);
          var r3 = false;
          o2 && "string" == typeof e2 && o2.test(e2) && (e2 = "'" + e2, r3 = true);
          var i2 = e2.toString().replace(u2, a2);
          return (r3 = r3 || true === n2 || "function" == typeof n2 && n2(e2, t2) || Array.isArray(n2) && n2[t2] || function(e3, t3) {
            for (var r4 = 0; r4 < t3.length; r4++) if (-1 < e3.indexOf(t3[r4])) return true;
            return false;
          }(i2, b.BAD_DELIMITERS) || -1 < i2.indexOf(m2) || " " === i2.charAt(0) || " " === i2.charAt(i2.length - 1)) ? s2 + i2 + s2 : i2;
        }
      } };
      if (b.RECORD_SEP = String.fromCharCode(30), b.UNIT_SEP = String.fromCharCode(31), b.BYTE_ORDER_MARK = "\uFEFF", b.BAD_DELIMITERS = ["\r", "\n", '"', b.BYTE_ORDER_MARK], b.WORKERS_SUPPORTED = !n && !!f.Worker, b.NODE_STREAM_INPUT = 1, b.LocalChunkSize = 10485760, b.RemoteChunkSize = 5242880, b.DefaultDelimiter = ",", b.Parser = E, b.ParserHandle = r, b.NetworkStreamer = l, b.FileStreamer = c, b.StringStreamer = p, b.ReadableStreamStreamer = g, f.jQuery) {
        var d = f.jQuery;
        d.fn.parse = function(o2) {
          var r2 = o2.config || {}, u2 = [];
          return this.each(function(e2) {
            if (!("INPUT" === d(this).prop("tagName").toUpperCase() && "file" === d(this).attr("type").toLowerCase() && f.FileReader) || !this.files || 0 === this.files.length) return true;
            for (var t = 0; t < this.files.length; t++) u2.push({ file: this.files[t], inputElem: this, instanceConfig: d.extend({}, r2) });
          }), e(), this;
          function e() {
            if (0 !== u2.length) {
              var e2, t, r3, i, n2 = u2[0];
              if (J(o2.before)) {
                var s2 = o2.before(n2.file, n2.inputElem);
                if ("object" == typeof s2) {
                  if ("abort" === s2.action) return e2 = "AbortError", t = n2.file, r3 = n2.inputElem, i = s2.reason, void (J(o2.error) && o2.error({ name: e2 }, t, r3, i));
                  if ("skip" === s2.action) return void h2();
                  "object" == typeof s2.config && (n2.instanceConfig = d.extend(n2.instanceConfig, s2.config));
                } else if ("skip" === s2) return void h2();
              }
              var a2 = n2.instanceConfig.complete;
              n2.instanceConfig.complete = function(e3) {
                J(a2) && a2(e3, n2.file, n2.inputElem), h2();
              }, b.parse(n2.file, n2.instanceConfig);
            } else J(o2.complete) && o2.complete();
          }
          function h2() {
            u2.splice(0, 1), e();
          }
        };
      }
      function h(e) {
        this._handle = null, this._finished = false, this._completed = false, this._halted = false, this._input = null, this._baseIndex = 0, this._partialLine = "", this._rowCount = 0, this._start = 0, this._nextChunk = null, this.isFirstChunk = true, this._completeResults = { data: [], errors: [], meta: {} }, (function(e2) {
          var t = w(e2);
          t.chunkSize = parseInt(t.chunkSize), e2.step || e2.chunk || (t.chunkSize = null);
          this._handle = new r(t), (this._handle.streamer = this)._config = t;
        }).call(this, e), this.parseChunk = function(e2, t) {
          if (this.isFirstChunk && J(this._config.beforeFirstChunk)) {
            var r2 = this._config.beforeFirstChunk(e2);
            void 0 !== r2 && (e2 = r2);
          }
          this.isFirstChunk = false, this._halted = false;
          var i = this._partialLine + e2;
          this._partialLine = "";
          var n2 = this._handle.parse(i, this._baseIndex, !this._finished);
          if (!this._handle.paused() && !this._handle.aborted()) {
            var s2 = n2.meta.cursor;
            this._finished || (this._partialLine = i.substring(s2 - this._baseIndex), this._baseIndex = s2), n2 && n2.data && (this._rowCount += n2.data.length);
            var a2 = this._finished || this._config.preview && this._rowCount >= this._config.preview;
            if (o) f.postMessage({ results: n2, workerId: b.WORKER_ID, finished: a2 });
            else if (J(this._config.chunk) && !t) {
              if (this._config.chunk(n2, this._handle), this._handle.paused() || this._handle.aborted()) return void (this._halted = true);
              n2 = void 0, this._completeResults = void 0;
            }
            return this._config.step || this._config.chunk || (this._completeResults.data = this._completeResults.data.concat(n2.data), this._completeResults.errors = this._completeResults.errors.concat(n2.errors), this._completeResults.meta = n2.meta), this._completed || !a2 || !J(this._config.complete) || n2 && n2.meta.aborted || (this._config.complete(this._completeResults, this._input), this._completed = true), a2 || n2 && n2.meta.paused || this._nextChunk(), n2;
          }
          this._halted = true;
        }, this._sendError = function(e2) {
          J(this._config.error) ? this._config.error(e2) : o && this._config.error && f.postMessage({ workerId: b.WORKER_ID, error: e2, finished: false });
        };
      }
      function l(e) {
        var i;
        (e = e || {}).chunkSize || (e.chunkSize = b.RemoteChunkSize), h.call(this, e), this._nextChunk = n ? function() {
          this._readChunk(), this._chunkLoaded();
        } : function() {
          this._readChunk();
        }, this.stream = function(e2) {
          this._input = e2, this._nextChunk();
        }, this._readChunk = function() {
          if (this._finished) this._chunkLoaded();
          else {
            if (i = new XMLHttpRequest(), this._config.withCredentials && (i.withCredentials = this._config.withCredentials), n || (i.onload = v(this._chunkLoaded, this), i.onerror = v(this._chunkError, this)), i.open(this._config.downloadRequestBody ? "POST" : "GET", this._input, !n), this._config.downloadRequestHeaders) {
              var e2 = this._config.downloadRequestHeaders;
              for (var t in e2) i.setRequestHeader(t, e2[t]);
            }
            if (this._config.chunkSize) {
              var r2 = this._start + this._config.chunkSize - 1;
              i.setRequestHeader("Range", "bytes=" + this._start + "-" + r2);
            }
            try {
              i.send(this._config.downloadRequestBody);
            } catch (e3) {
              this._chunkError(e3.message);
            }
            n && 0 === i.status && this._chunkError();
          }
        }, this._chunkLoaded = function() {
          4 === i.readyState && (i.status < 200 || 400 <= i.status ? this._chunkError() : (this._start += this._config.chunkSize ? this._config.chunkSize : i.responseText.length, this._finished = !this._config.chunkSize || this._start >= function(e2) {
            var t = e2.getResponseHeader("Content-Range");
            if (null === t) return -1;
            return parseInt(t.substring(t.lastIndexOf("/") + 1));
          }(i), this.parseChunk(i.responseText)));
        }, this._chunkError = function(e2) {
          var t = i.statusText || e2;
          this._sendError(new Error(t));
        };
      }
      function c(e) {
        var i, n2;
        (e = e || {}).chunkSize || (e.chunkSize = b.LocalChunkSize), h.call(this, e);
        var s2 = "undefined" != typeof FileReader;
        this.stream = function(e2) {
          this._input = e2, n2 = e2.slice || e2.webkitSlice || e2.mozSlice, s2 ? ((i = new FileReader()).onload = v(this._chunkLoaded, this), i.onerror = v(this._chunkError, this)) : i = new FileReaderSync(), this._nextChunk();
        }, this._nextChunk = function() {
          this._finished || this._config.preview && !(this._rowCount < this._config.preview) || this._readChunk();
        }, this._readChunk = function() {
          var e2 = this._input;
          if (this._config.chunkSize) {
            var t = Math.min(this._start + this._config.chunkSize, this._input.size);
            e2 = n2.call(e2, this._start, t);
          }
          var r2 = i.readAsText(e2, this._config.encoding);
          s2 || this._chunkLoaded({ target: { result: r2 } });
        }, this._chunkLoaded = function(e2) {
          this._start += this._config.chunkSize, this._finished = !this._config.chunkSize || this._start >= this._input.size, this.parseChunk(e2.target.result);
        }, this._chunkError = function() {
          this._sendError(i.error);
        };
      }
      function p(e) {
        var r2;
        h.call(this, e = e || {}), this.stream = function(e2) {
          return r2 = e2, this._nextChunk();
        }, this._nextChunk = function() {
          if (!this._finished) {
            var e2, t = this._config.chunkSize;
            return t ? (e2 = r2.substring(0, t), r2 = r2.substring(t)) : (e2 = r2, r2 = ""), this._finished = !r2, this.parseChunk(e2);
          }
        };
      }
      function g(e) {
        h.call(this, e = e || {});
        var t = [], r2 = true, i = false;
        this.pause = function() {
          h.prototype.pause.apply(this, arguments), this._input.pause();
        }, this.resume = function() {
          h.prototype.resume.apply(this, arguments), this._input.resume();
        }, this.stream = function(e2) {
          this._input = e2, this._input.on("data", this._streamData), this._input.on("end", this._streamEnd), this._input.on("error", this._streamError);
        }, this._checkIsFinished = function() {
          i && 1 === t.length && (this._finished = true);
        }, this._nextChunk = function() {
          this._checkIsFinished(), t.length ? this.parseChunk(t.shift()) : r2 = true;
        }, this._streamData = v(function(e2) {
          try {
            t.push("string" == typeof e2 ? e2 : e2.toString(this._config.encoding)), r2 && (r2 = false, this._checkIsFinished(), this.parseChunk(t.shift()));
          } catch (e3) {
            this._streamError(e3);
          }
        }, this), this._streamError = v(function(e2) {
          this._streamCleanUp(), this._sendError(e2);
        }, this), this._streamEnd = v(function() {
          this._streamCleanUp(), i = true, this._streamData("");
        }, this), this._streamCleanUp = v(function() {
          this._input.removeListener("data", this._streamData), this._input.removeListener("end", this._streamEnd), this._input.removeListener("error", this._streamError);
        }, this);
      }
      function r(m2) {
        var a2, o2, u2, i = Math.pow(2, 53), n2 = -i, s2 = /^\s*-?(\d+\.?|\.\d+|\d+\.\d+)([eE][-+]?\d+)?\s*$/, h2 = /^((\d{4}-[01]\d-[0-3]\dT[0-2]\d:[0-5]\d:[0-5]\d\.\d+([+-][0-2]\d:[0-5]\d|Z))|(\d{4}-[01]\d-[0-3]\dT[0-2]\d:[0-5]\d:[0-5]\d([+-][0-2]\d:[0-5]\d|Z))|(\d{4}-[01]\d-[0-3]\dT[0-2]\d:[0-5]\d([+-][0-2]\d:[0-5]\d|Z)))$/, t = this, r2 = 0, f2 = 0, d2 = false, e = false, l2 = [], c2 = { data: [], errors: [], meta: {} };
        if (J(m2.step)) {
          var p2 = m2.step;
          m2.step = function(e2) {
            if (c2 = e2, _2()) g2();
            else {
              if (g2(), 0 === c2.data.length) return;
              r2 += e2.data.length, m2.preview && r2 > m2.preview ? o2.abort() : (c2.data = c2.data[0], p2(c2, t));
            }
          };
        }
        function y2(e2) {
          return "greedy" === m2.skipEmptyLines ? "" === e2.join("").trim() : 1 === e2.length && 0 === e2[0].length;
        }
        function g2() {
          return c2 && u2 && (k("Delimiter", "UndetectableDelimiter", "Unable to auto-detect delimiting character; defaulted to '" + b.DefaultDelimiter + "'"), u2 = false), m2.skipEmptyLines && (c2.data = c2.data.filter(function(e2) {
            return !y2(e2);
          })), _2() && function() {
            if (!c2) return;
            function e2(e3, t3) {
              J(m2.transformHeader) && (e3 = m2.transformHeader(e3, t3)), l2.push(e3);
            }
            if (Array.isArray(c2.data[0])) {
              for (var t2 = 0; _2() && t2 < c2.data.length; t2++) c2.data[t2].forEach(e2);
              c2.data.splice(0, 1);
            } else c2.data.forEach(e2);
          }(), function() {
            if (!c2 || !m2.header && !m2.dynamicTyping && !m2.transform) return c2;
            function e2(e3, t3) {
              var r3, i2 = m2.header ? {} : [];
              for (r3 = 0; r3 < e3.length; r3++) {
                var n3 = r3, s3 = e3[r3];
                m2.header && (n3 = r3 >= l2.length ? "__parsed_extra" : l2[r3]), m2.transform && (s3 = m2.transform(s3, n3)), s3 = v2(n3, s3), "__parsed_extra" === n3 ? (i2[n3] = i2[n3] || [], i2[n3].push(s3)) : i2[n3] = s3;
              }
              return m2.header && (r3 > l2.length ? k("FieldMismatch", "TooManyFields", "Too many fields: expected " + l2.length + " fields but parsed " + r3, f2 + t3) : r3 < l2.length && k("FieldMismatch", "TooFewFields", "Too few fields: expected " + l2.length + " fields but parsed " + r3, f2 + t3)), i2;
            }
            var t2 = 1;
            !c2.data.length || Array.isArray(c2.data[0]) ? (c2.data = c2.data.map(e2), t2 = c2.data.length) : c2.data = e2(c2.data, 0);
            m2.header && c2.meta && (c2.meta.fields = l2);
            return f2 += t2, c2;
          }();
        }
        function _2() {
          return m2.header && 0 === l2.length;
        }
        function v2(e2, t2) {
          return r3 = e2, m2.dynamicTypingFunction && void 0 === m2.dynamicTyping[r3] && (m2.dynamicTyping[r3] = m2.dynamicTypingFunction(r3)), true === (m2.dynamicTyping[r3] || m2.dynamicTyping) ? "true" === t2 || "TRUE" === t2 || "false" !== t2 && "FALSE" !== t2 && (function(e3) {
            if (s2.test(e3)) {
              var t3 = parseFloat(e3);
              if (n2 < t3 && t3 < i) return true;
            }
            return false;
          }(t2) ? parseFloat(t2) : h2.test(t2) ? new Date(t2) : "" === t2 ? null : t2) : t2;
          var r3;
        }
        function k(e2, t2, r3, i2) {
          var n3 = { type: e2, code: t2, message: r3 };
          void 0 !== i2 && (n3.row = i2), c2.errors.push(n3);
        }
        this.parse = function(e2, t2, r3) {
          var i2 = m2.quoteChar || '"';
          if (m2.newline || (m2.newline = function(e3, t3) {
            e3 = e3.substring(0, 1048576);
            var r4 = new RegExp(Q(t3) + "([^]*?)" + Q(t3), "gm"), i3 = (e3 = e3.replace(r4, "")).split("\r"), n4 = e3.split("\n"), s4 = 1 < n4.length && n4[0].length < i3[0].length;
            if (1 === i3.length || s4) return "\n";
            for (var a3 = 0, o3 = 0; o3 < i3.length; o3++) "\n" === i3[o3][0] && a3++;
            return a3 >= i3.length / 2 ? "\r\n" : "\r";
          }(e2, i2)), u2 = false, m2.delimiter) J(m2.delimiter) && (m2.delimiter = m2.delimiter(e2), c2.meta.delimiter = m2.delimiter);
          else {
            var n3 = function(e3, t3, r4, i3, n4) {
              var s4, a3, o3, u3;
              n4 = n4 || [",", "	", "|", ";", b.RECORD_SEP, b.UNIT_SEP];
              for (var h3 = 0; h3 < n4.length; h3++) {
                var f3 = n4[h3], d3 = 0, l3 = 0, c3 = 0;
                o3 = void 0;
                for (var p3 = new E({ comments: i3, delimiter: f3, newline: t3, preview: 10 }).parse(e3), g3 = 0; g3 < p3.data.length; g3++) if (r4 && y2(p3.data[g3])) c3++;
                else {
                  var _3 = p3.data[g3].length;
                  l3 += _3, void 0 !== o3 ? 0 < _3 && (d3 += Math.abs(_3 - o3), o3 = _3) : o3 = _3;
                }
                0 < p3.data.length && (l3 /= p3.data.length - c3), (void 0 === a3 || d3 <= a3) && (void 0 === u3 || u3 < l3) && 1.99 < l3 && (a3 = d3, s4 = f3, u3 = l3);
              }
              return { successful: !!(m2.delimiter = s4), bestDelimiter: s4 };
            }(e2, m2.newline, m2.skipEmptyLines, m2.comments, m2.delimitersToGuess);
            n3.successful ? m2.delimiter = n3.bestDelimiter : (u2 = true, m2.delimiter = b.DefaultDelimiter), c2.meta.delimiter = m2.delimiter;
          }
          var s3 = w(m2);
          return m2.preview && m2.header && s3.preview++, a2 = e2, o2 = new E(s3), c2 = o2.parse(a2, t2, r3), g2(), d2 ? { meta: { paused: true } } : c2 || { meta: { paused: false } };
        }, this.paused = function() {
          return d2;
        }, this.pause = function() {
          d2 = true, o2.abort(), a2 = J(m2.chunk) ? "" : a2.substring(o2.getCharIndex());
        }, this.resume = function() {
          t.streamer._halted ? (d2 = false, t.streamer.parseChunk(a2, true)) : setTimeout(t.resume, 3);
        }, this.aborted = function() {
          return e;
        }, this.abort = function() {
          e = true, o2.abort(), c2.meta.aborted = true, J(m2.complete) && m2.complete(c2), a2 = "";
        };
      }
      function Q(e) {
        return e.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
      }
      function E(j) {
        var z, M = (j = j || {}).delimiter, P = j.newline, U = j.comments, q = j.step, N = j.preview, B = j.fastMode, K = z = void 0 === j.quoteChar || null === j.quoteChar ? '"' : j.quoteChar;
        if (void 0 !== j.escapeChar && (K = j.escapeChar), ("string" != typeof M || -1 < b.BAD_DELIMITERS.indexOf(M)) && (M = ","), U === M) throw new Error("Comment character same as delimiter");
        true === U ? U = "#" : ("string" != typeof U || -1 < b.BAD_DELIMITERS.indexOf(U)) && (U = false), "\n" !== P && "\r" !== P && "\r\n" !== P && (P = "\n");
        var W = 0, H = false;
        this.parse = function(i, t, r2) {
          if ("string" != typeof i) throw new Error("Input must be a string");
          var n2 = i.length, e = M.length, s2 = P.length, a2 = U.length, o2 = J(q), u2 = [], h2 = [], f2 = [], d2 = W = 0;
          if (!i) return L();
          if (j.header && !t) {
            var l2 = i.split(P)[0].split(M), c2 = [], p2 = {}, g2 = false;
            for (var _2 in l2) {
              var m2 = l2[_2];
              J(j.transformHeader) && (m2 = j.transformHeader(m2, _2));
              var y2 = m2, v2 = p2[m2] || 0;
              for (0 < v2 && (g2 = true, y2 = m2 + "_" + v2), p2[m2] = v2 + 1; c2.includes(y2); ) y2 = y2 + "_" + v2;
              c2.push(y2);
            }
            if (g2) {
              var k = i.split(P);
              k[0] = c2.join(M), i = k.join(P);
            }
          }
          if (B || false !== B && -1 === i.indexOf(z)) {
            for (var b2 = i.split(P), E2 = 0; E2 < b2.length; E2++) {
              if (f2 = b2[E2], W += f2.length, E2 !== b2.length - 1) W += P.length;
              else if (r2) return L();
              if (!U || f2.substring(0, a2) !== U) {
                if (o2) {
                  if (u2 = [], I(f2.split(M)), F(), H) return L();
                } else I(f2.split(M));
                if (N && N <= E2) return u2 = u2.slice(0, N), L(true);
              }
            }
            return L();
          }
          for (var w2 = i.indexOf(M, W), R = i.indexOf(P, W), C = new RegExp(Q(K) + Q(z), "g"), S = i.indexOf(z, W); ; ) if (i[W] !== z) if (U && 0 === f2.length && i.substring(W, W + a2) === U) {
            if (-1 === R) return L();
            W = R + s2, R = i.indexOf(P, W), w2 = i.indexOf(M, W);
          } else if (-1 !== w2 && (w2 < R || -1 === R)) f2.push(i.substring(W, w2)), W = w2 + e, w2 = i.indexOf(M, W);
          else {
            if (-1 === R) break;
            if (f2.push(i.substring(W, R)), D(R + s2), o2 && (F(), H)) return L();
            if (N && u2.length >= N) return L(true);
          }
          else for (S = W, W++; ; ) {
            if (-1 === (S = i.indexOf(z, S + 1))) return r2 || h2.push({ type: "Quotes", code: "MissingQuotes", message: "Quoted field unterminated", row: u2.length, index: W }), T();
            if (S === n2 - 1) return T(i.substring(W, S).replace(C, z));
            if (z !== K || i[S + 1] !== K) {
              if (z === K || 0 === S || i[S - 1] !== K) {
                -1 !== w2 && w2 < S + 1 && (w2 = i.indexOf(M, S + 1)), -1 !== R && R < S + 1 && (R = i.indexOf(P, S + 1));
                var O = A(-1 === R ? w2 : Math.min(w2, R));
                if (i.substr(S + 1 + O, e) === M) {
                  f2.push(i.substring(W, S).replace(C, z)), i[W = S + 1 + O + e] !== z && (S = i.indexOf(z, W)), w2 = i.indexOf(M, W), R = i.indexOf(P, W);
                  break;
                }
                var x = A(R);
                if (i.substring(S + 1 + x, S + 1 + x + s2) === P) {
                  if (f2.push(i.substring(W, S).replace(C, z)), D(S + 1 + x + s2), w2 = i.indexOf(M, W), S = i.indexOf(z, W), o2 && (F(), H)) return L();
                  if (N && u2.length >= N) return L(true);
                  break;
                }
                h2.push({ type: "Quotes", code: "InvalidQuotes", message: "Trailing quote on quoted field is malformed", row: u2.length, index: W }), S++;
              }
            } else S++;
          }
          return T();
          function I(e2) {
            u2.push(e2), d2 = W;
          }
          function A(e2) {
            var t2 = 0;
            if (-1 !== e2) {
              var r3 = i.substring(S + 1, e2);
              r3 && "" === r3.trim() && (t2 = r3.length);
            }
            return t2;
          }
          function T(e2) {
            return r2 || (void 0 === e2 && (e2 = i.substring(W)), f2.push(e2), W = n2, I(f2), o2 && F()), L();
          }
          function D(e2) {
            W = e2, I(f2), f2 = [], R = i.indexOf(P, W);
          }
          function L(e2) {
            return { data: u2, errors: h2, meta: { delimiter: M, linebreak: P, aborted: H, truncated: !!e2, cursor: d2 + (t || 0) } };
          }
          function F() {
            q(L()), u2 = [], h2 = [];
          }
        }, this.abort = function() {
          H = true;
        }, this.getCharIndex = function() {
          return W;
        };
      }
      function _(e) {
        var t = e.data, r2 = a[t.workerId], i = false;
        if (t.error) r2.userError(t.error, t.file);
        else if (t.results && t.results.data) {
          var n2 = { abort: function() {
            i = true, m(t.workerId, { data: [], errors: [], meta: { aborted: true } });
          }, pause: y, resume: y };
          if (J(r2.userStep)) {
            for (var s2 = 0; s2 < t.results.data.length && (r2.userStep({ data: t.results.data[s2], errors: t.results.errors, meta: t.results.meta }, n2), !i); s2++) ;
            delete t.results;
          } else J(r2.userChunk) && (r2.userChunk(t.results, n2, t.file), delete t.results);
        }
        t.finished && !i && m(t.workerId, t.results);
      }
      function m(e, t) {
        var r2 = a[e];
        J(r2.userComplete) && r2.userComplete(t), r2.terminate(), delete a[e];
      }
      function y() {
        throw new Error("Not implemented.");
      }
      function w(e) {
        if ("object" != typeof e || null === e) return e;
        var t = Array.isArray(e) ? [] : {};
        for (var r2 in e) t[r2] = w(e[r2]);
        return t;
      }
      function v(e, t) {
        return function() {
          e.apply(t, arguments);
        };
      }
      function J(e) {
        return "function" == typeof e;
      }
      return o && (f.onmessage = function(e) {
        var t = e.data;
        void 0 === b.WORKER_ID && t && (b.WORKER_ID = t.workerId);
        if ("string" == typeof t.input) f.postMessage({ workerId: b.WORKER_ID, results: b.parse(t.input, t.config), finished: true });
        else if (f.File && t.input instanceof File || t.input instanceof Object) {
          var r2 = b.parse(t.input, t.config);
          r2 && f.postMessage({ workerId: b.WORKER_ID, results: r2, finished: true });
        }
      }), (l.prototype = Object.create(h.prototype)).constructor = l, (c.prototype = Object.create(h.prototype)).constructor = c, (p.prototype = Object.create(p.prototype)).constructor = p, (g.prototype = Object.create(h.prototype)).constructor = g, b;
    });
  }
});
export default require_papaparse_min();
/*! Bundled license information:

papaparse/papaparse.min.js:
  (* @license
  Papa Parse
  v5.4.1
  https://github.com/mholt/PapaParse
  License: MIT
  *)
*/
//# sourceMappingURL=papaparse.js.map
