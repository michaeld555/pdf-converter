from docx2pdf import convert
import sys

args = sys.argv[1:]

def convert_docx_to_pdf(input_file, output_file=None):
    convert(input_file, output_file)

input_file = args[0]
output_file = args[1]

convert_docx_to_pdf(input_file, output_file)