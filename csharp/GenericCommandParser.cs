
using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

namespace ext_printserver {

    /**
     * parser for command format:
     * command argname="arg_value" argname2="arg_value"
     */
    class CommandParser {

        private string command = null;
        private Dictionary<string, string> argv;

        /**
         * parse commands, args, value
         */
        public CommandParser(string command) {

            string command_tmp = "";
            string arg_tmp = "";
            string value_tmp = "";

            bool read_arg_val = false;
            bool arg_val_in_str = false;

            this.argv = new Dictionary<string, string>();

            for (int i = 0; i < command.Length; i++) {
                if (this.command == null) {
                    if (command[i] == ' ') {
                        this.command = command_tmp;
                        continue;
                    }
                    else {
                        if (i == command.Length - 1) {
                            command_tmp += command[i];
                            this.command = command_tmp;
                            continue;
                        }
                        command_tmp += command[i];
                    }
                }
                else {
                    if (!read_arg_val) {
                        if (command[i] == '=') {
                            read_arg_val = true; continue;
                        }
                        else {
                            if (command[i] == ' ') continue;
                            arg_tmp += command[i];
                        }
                    }
                    else {
                        if (command[i] == '"') {
                            if (!arg_val_in_str) {
                                arg_val_in_str = true;
                                continue;
                            }
                            else {
                                arg_val_in_str = false;
                                read_arg_val = false;
                                string copyarg = arg_tmp; string copyval = value_tmp;
                                this.argv.Add(copyarg, copyval);
                                arg_tmp = "";
                                value_tmp = "";
                                continue;
                            }
                        }
                        else {
                            if (arg_val_in_str) {
                                value_tmp += command[i];
                            }
                        }
                    }
                }
            }
            if (this.command == null) {
                this.command = "";
            }
        }

        /**
         * returns if there exists such an argument for this command
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
         * returns the op/command associated with the constructed
         * command string
         */
        public string GetCommand() {
            return this.command;
        }

    }

}

