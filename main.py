import sys
import json
import mysql.connector
from mysql.connector import Error
import io
import fitz  # PyMuPDF
from sklearn.feature_extraction.text import CountVectorizer
from sklearn.metrics.pairwise import cosine_similarity
import spacy
import nltk
from nltk.corpus import stopwords

# Ensure necessary NLTK resources are downloaded
nltk.download('punkt', quiet=True)
nltk.download('averaged_perceptron_tagger', quiet=True)
nltk.download('stopwords', quiet=True)

# Load SpaCy model and stopwords
nlp = spacy.load('en_core_web_sm')
stop_words_spacy = nlp.Defaults.stop_words
stop_words_nltk = set(stopwords.words('english'))

def connect_to_database():
    try:
        connection = mysql.connector.connect(
            host='localhost',
            database='opencats',
            user='Opencats',
            password='Opencats@123'
        )
        print("Connected to OpenCats")
        return connection
    except Error as e:
        print(json.dumps({"error": f"Error connecting to MariaDB Platform: {e}"}))
        return None

def extract_text_from_pdf_blob(pdf_blob):
    try:
        with io.BytesIO(pdf_blob) as stream:
            doc = fitz.open(stream=stream, filetype="pdf")
            text = ""
            for page in doc:
                text += page.get_text()
                print(text=="")
        return text.lower()
    except Exception as e:
        print(json.dumps({"error": f"Error extracting text from PDF: {e}"}))
        return ""
def process_resume(resume_text):
    keywords_resume_spacy = spacy_keywords(resume_text)
    keywords_resume_nltk = nltk_keywords(resume_text)
    return keywords_resume_spacy, keywords_resume_nltk

def calculate_similarities(resume_text, keywords_resume_spacy, keywords_resume_nltk, job_description):
    # Cosine Similarity
    text = [resume_text, job_description]
    cv = CountVectorizer()
    count_matrix = cv.fit_transform(text)
    cosine = cosine_similarity(count_matrix)[0][1]

    # SpaCy Keywords Matching
    keywords_jd_spacy = spacy_keywords(job_description)
    keywords_matched_spacy = set(keywords_jd_spacy).intersection(keywords_resume_spacy)
    percentage_spacy = len(keywords_matched_spacy) / len(keywords_resume_spacy) if keywords_resume_spacy else 0

    # NLTK Keywords Matching
    keywords_jd_nltk = nltk_keywords(job_description)
    keywords_matched_nltk = set(keywords_jd_nltk).intersection(keywords_resume_nltk)
    percentage_nltk = len(keywords_matched_nltk) / len(keywords_resume_nltk) if keywords_resume_nltk else 0

    # Combined Accuracy
    w_C, w_S, w_N = 0.2, 0.4, 0.4
    combined_accuracy = (w_C * cosine) + (w_S * percentage_spacy) + (w_N * percentage_nltk)

    return combined_accuracy

def spacy_keywords(data):
    tokens = nlp(data)
    keywords = [str(tok) for tok in tokens if tok.pos_ in ['PROPN', 'NOUN'] and str(tok) not in stop_words_spacy]
    return sorted(set(keywords))

def nltk_keywords(data):
    tokens = nltk.word_tokenize(data)
    pos_tagged_tokens = nltk.pos_tag(tokens)
    keywords = [t[0] for t in pos_tagged_tokens if t[1] in ['NNP', 'NN'] and t[0] not in stop_words_nltk]
    return sorted(set(keywords))

def main(job_description, match_percentage):
    connection = connect_to_database()
    if not connection:
        return json.dumps({"error": "Database connection failed"})

    try:
        cursor = connection.cursor(dictionary=True)
        query = """
        SELECT a.attachment_id as id, c.first_name, c.last_name, a.text
        FROM attachment a
        JOIN candidate c ON a.data_item_id = c.candidate_id
        WHERE a.data_item_type = 'Candidate'
        """
        cursor.execute(query)
        resumes = cursor.fetchall()
        print(len(resumes))

        results = []
        for resume in resumes:
            resume_text = extract_text_from_pdf_blob(resume['resume_text'])
            keywords_resume_spacy, keywords_resume_nltk = process_resume(resume_text)
            similarity = calculate_similarities(resume_text, keywords_resume_spacy, keywords_resume_nltk, job_description)
            
            if similarity * 100 >= float(match_percentage):
                results.append({
                    "id": resume['id'],
                    "name": f"{resume['first_name']} {resume['last_name']}",
                    "match": f"{similarity:.2%}"
                })

        results.sort(key=lambda x: float(x['match'].strip('%')), reverse=True)
        return json.dumps({"resumes": results})

    except Error as e:
        return json.dumps({"error": str(e)})

    finally:
        if connection.is_connected():
            cursor.close()
            connection.close()

if __name__ == "__main__":
    if len(sys.argv) != 3:
        print(json.dumps({"error": "Invalid number of arguments"}))
    else:
        job_description = sys.argv[1]
        match_percentage = sys.argv[2]
        print(main(job_description, match_percentage))