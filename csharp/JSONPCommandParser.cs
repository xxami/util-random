
using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

namespace ext_printserver {

    class HTTP {

        /**
         * header response for jsonp requests
         */
        public static string JSONPHeaderResponse =
            "HTTP/1.1 200 OK\r\n" +
            "Content-Type: text/javascript; charset=UTF-8\r\n" +
            "Access-Control-Allow-Origin: *\r\n" +
            "X-Content-Type-Options: nosniff\r\n" +
            "X-XSS-Protection: 1; mode=block\r\n" +
            "Connection: close\r\n" +
            "\r\n";

        /**
         * parser for jsonp get requests
         * format: ?callback=callback&command=command&arg1=value
         */
        public class JSONPGetRequestParser {

            private string command = null;
            private string callback = null;
            private Dictionary<string, string> argv;

            /**
             * parse args/values, escape url encoded values
             */
            public JSONPGetRequestParser(string command) {

                bool read_key = false;
                bool read_val = false;
                string key_tmp = "";
                string val_tmp = "";

                this.argv = new Dictionary<string, string>();
                int len = command.Length;

                for (int i = 0; i < len; i++) {

                    if (!read_val && !read_key) {
                        if (command[i] == '?') {
                            read_key = true;
                            continue;
                        }
                    }

                    /**
                     * keys
                     */
                    else if (read_key) {
                        if (command[i] == '=') {
                            read_key = false; read_val = true;
                            continue;
                        }
                        else if (command[i] == '+') {
                            key_tmp += ' ';
                            continue;
                        }
                        else {
                            key_tmp += command[i];
                            continue;
                        }
                    }

                    /**
                     * values
                     */
                    else if (read_val) {
                        if (i == len - 1) {
                            if (command[i] == '+') val_tmp += ' ';
                            else val_tmp += command[i];
                            this.argv.Add(key_tmp, 
                                Uri.UnescapeDataString(val_tmp));
                            break;
                        }
                        else if (command[i] == '&') {
                            read_val = false; read_key = true;
                            this.argv.Add(key_tmp,
                                Uri.UnescapeDataString(val_tmp));
                            key_tmp = "";
                            val_tmp = "";
                            continue;
                        }
                        else if (command[i] == '+') {
                            val_tmp += ' ';
                            continue;
                        }
                        else {
                            val_tmp += command[i];
                            continue;
                        }
                    }
                }

                /**
                 * save the required data
                 */
                this.command = (this.argv.ContainsKey("command")) ?
                    this.argv["command"] : "";
                this.callback = (this.argv.ContainsKey("callback")) ?
                    this.argv["callback"] : "";
            }

            /**
             * returns if there exists such an argument for this request
             */
            public bool HasArg(string arg) {
                if (this.argv.ContainsKey(arg))
                    return true;
                else return false;
            }

            /**
             * returns the value for the given existing argument
             */
            public string GetArg(string arg) {
                return this.argv[arg];
            }

            /**
             * returns the command argument - required
             */
            public string GetCommand() {
                return this.command;
            }

            /**
             * returns the callback argument - required
             */
            public string GetCallback() {
                return this.callback;
            }

        }

    }

}

