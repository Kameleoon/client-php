conf_file=/etc/kameleoon/php-client.json
while [[ $# -gt 0 ]]
do
	key="$1"
	case $key in
		--conf)
		conf_file="$2"
		shift
		shift
		;;
		*)
		shift
		;;
	esac
done
if [ -f "$conf_file" ]
then
    kameleoon_work_dir=$("cat" $conf_file | "python" -c 'import json,sys;obj=json.load(sys.stdin);print(obj["kameleoon_work_dir"])')
fi
if [ -z "$kameleoon_work_dir" ]
then
	kameleoon_work_dir=/tmp/kameleoon/php-client/
fi
request_files=$("ls" -rt $kameleoon_work_dir/requests-*.sh 2>/dev/null)
previous_minute=$(($("date" +"%s")/60-1))
for request_file in $request_files
do
	request_file_minute=$("echo" "$request_file" | "sed" "s/.*requests\-\(.*\)\.sh/\1/")
	if [ $request_file_minute -lt $previous_minute ]
	then
		"mv" -f $request_file "${request_file}.lock"
	fi
done
for request_file in $request_files
do
	if [ -f "${request_file}.lock" ]
	then
		"source" "${request_file}.lock";"rm" -f "${request_file}.lock"
	fi
done
