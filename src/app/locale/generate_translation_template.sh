find ../../ -type f \( -iname '*.php' -o -iname '*.phtml' \) | xgettext -L PHP --keyword="T_" -i --no-wrap -f -
