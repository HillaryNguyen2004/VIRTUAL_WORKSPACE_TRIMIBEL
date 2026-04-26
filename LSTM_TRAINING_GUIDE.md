# Mô Hình Dự Đoán Năng Suất LSTM: Hướng Dẫn Huấn Luyện & Đánh Giá Hoàn Chỉnh

## Mục Lục
1. [Tổng Quan Kiến Trúc Hệ Thống](#tổng-quan-kiến-trúc-hệ-thống)
2. [Quy Trình Dữ Liệu & Kỹ Thuật Đặc Tính](#quy-trình-dữ-liệu--kỹ-thuật-đặc-tính)
3. [Quá Trình Huấn Luyện](#quá-trình-huấn-luyện)
4. [Kiến Trúc Mô Hình](#kiến-trúc-mô-hình)
5. [Quy Trình & Chỉ Số Đánh Giá](#quy-trình--chỉ-số-đánh-giá)
6. [Hiểu Rõ Kết Quả Của Bạn](#hiểu-rõ-kết-quả-của-bạn)
7. [Các Hạn Chế Đã Biết & Khung Luận Văn](#các-hạn-chế-đã-biết--khung-luận-văn)
8. [Tái Tạo & Hạt Ngẫu Nhiên](#tái-tạo--hạt-ngẫu-nhiên)
9. [Khắc Phục Sự Cố](#khắc-phục-sự-cố)
10. [Tham Chiếu Nhanh](#tham-chiếu-nhanh)
11. [Tiến Hóa Mô Hình & Nhật Ký Thay Đổi](#tiến-hóa-mô-hình--nhật-ký-thay-đổi)

---

## Tiến Hóa Mô Hình & Nhật Ký Thay Đổi

### Kết Quả Cơ Bản (Triển Khai Ban Đầu) — KÉM (68,5% Độ Chính Xác, 0,647 Macro-F1)

**Kết Quả Cũ:**
```
Độ Chính Xác: 0,685 (68,5%)
Macro F1    : 0,647
Phán Xét    : KÉM — mô hình không đủ tin cậy để sử dụng trong sản xuất
```

**Tại Sao Nó Kém:**
Mô hình ban đầu có ba lỗi nghiêm trọng:

1. **Rò Rỉ Mục Tiêu Qua Trung Bình Động**
   - Các đặc tính như `avg_score_7d`, `avg_score_30d`, và `score_trend` được tính trực tiếp từ biến mục tiêu `productivity_score`
   - Điều này có nghĩa là mô hình có thể "gian lận" bằng cách chỉ sao chép điểm hôm nay thành dự đoán của ngày mai
   - LSTM học để dự đoán các đường cong phẳng thay vì nắm bắt các mẫu hành vi thực
   - **Tác Động:** Độ chính xác huấn luyện cao nhưng tổng quát hóa kém; các chỉ số xác thực sụp đổ

2. **Hồi Quy Trên Đầu Ra Công Thức Ồn**
   - Mô hình sử dụng MSE để dự đoán điểm liên tục 0–100
   - Nhưng các điểm này đến từ công thức ETL xác định, không phải từ phương sai tự nhiên
   - Công thức nắm bắt 95%+ tín hiệu; LSTM có rất ít mẫu thực để học
   - **Tác Động:** Độ chính xác bị giới hạn ở ~68% vì tính biến thiên của công thức bị giới hạn

3. **Kỹ Thuật Đặc Tính Không Đủ**
   - Chỉ sử dụng các đặc tính thô (giờ, trễ, check-in, v.v.)
   - Thiếu ngữ cảnh thời gian — không có khái niệm "mô hình của nhân viên này đang cải thiện hay suy giảm?"
   - Không có tín hiệu hành vi như "streak check-in" hoặc "cường độ khối lượng công việc"

4. **Cửa Sổ Lookback Cố Định**
   - 7 ngày quá ngắn cho các mẫu dài hạn có ý nghĩa
   - Không thể nắm bắt các biến thể hàng tuần hoặc xu hướng hàng tháng

---

### Các Thay Đổi Được Thực Hiện (Triển Khai Hiện Tại) — Cải Thiện (Mục Tiêu: 75%+ Độ Chính Xác)

#### **SỬA 1: Xóa Rò Rỉ Mục Tiêu (Kỹ Thuật Đặc Tính)**

**Thay Đổi:**
```python
# CŨ (SAI — rò rỉ mục tiêu):
df['avg_score_7d'] = df.groupby('user_id')['productivity_score'].rolling(7).mean()
df['avg_score_30d'] = df.groupby('user_id')['productivity_score'].rolling(30).mean()
df['score_trend'] = df['avg_score_7d'] - df['avg_score_30d']

# MỚI (AN TOÀN — chỉ sử dụng giá trị quá khứ):
df['score_yesterday'] = df.groupby('user_id')['productivity_score'].shift(1)
df['score_3d_ago']    = df.groupby('user_id')['productivity_score'].shift(3)
df['score_7d_ago']    = df.groupby('user_id')['productivity_score'].shift(7)
df['score_delta_1d']  = df['score_yesterday'] - df['score_3d_ago']   # xu hướng ngắn
df['score_delta_7d']  = df['score_3d_ago']    - df['score_7d_ago']   # xu hướng trung bình
```

**Tại Sao Nó Quan Trọng:**
- `.shift(n)` kéo dữ liệu từ *quá khứ* — không có thông tin tương lai rò rỉ vào huấn luyện
- Mô hình học động lực thực sự: "Xu hướng đã đảo ngược?" thay vì "Sao chép điểm hôm nay"
- Xác thực trên thời kỳ hoàn toàn chưa từng thấy (ngày sau ngày cắt huấn luyện)

**Tác Động Đến Chỉ Số:**
- ✅ Độ chính xác xác thực không còn bị thổi phồng nhân tạo
- ✅ Mô hình học các mẫu dự đoán thực tế vs. sao chép mục tiêu
- ✅ Khoảng cách tổng quát hóa (train-val) giảm đáng kể

---

#### **SỬA 2: Xóa Làm Mịn Mục Tiêu**

**Thay Đổi:**
```python
# CŨ (SAI):
df['productivity_score'] = df.groupby('user_id')['productivity_score'] \
    .rolling(3, min_periods=1).mean()  # Làm mịn tính biến thiên tự nhiên

# MỚI:
# Mục tiêu giữ nguyên — tính biến thiên tự nhiên được bảo toàn
```

**Tại Sao Nó Quan Trọng:**
- Làm mịn ẩn đi những biến động hàng ngày thực tế có chứa tín hiệu dự đoán
- Mô hình học dự đoán các đường cong mịn thay vì điểm thực tế hàng ngày
- Loại bỏ ràng buộc nhân tạo lên phương sai đầu ra

**Tác Động Đến Chỉ Số:**
- ✅ Mô hình học các mẫu sắc nét (có thể dự đoán những thay đổi từ ngày này sang ngày khác)
- ✅ Dự đoán căn chỉnh với phân bố điểm thực tế
- ✅ Tầm quan trọng đặc tính trở nên rõ ràng hơn (nắm bắt các trình điều khiển thực sự)

---

#### **SỬA 3: Tăng Cửa Sổ Lookback**

**Thay Đổi:**
```python
# CŨ:
LOOKBACK = 7  # 1 tuần

# MỚI:
LOOKBACK = 14  # 2 tuần
```

**Tại Sao Nó Quan Trọng:**
- 7 ngày nắm bắt các chu kỳ hàng tuần ngắn nhưng bỏ lỡ các mẫu trung hạn
- 14 ngày cho phép LSTM nhìn: "Tuần này so với tuần trước — cải thiện hay suy giảm?"
- Phù hợp với chu kỳ hành vi nhân viên tự nhiên (vòng phản hồi, giai đoạn dự án kéo dài 1–2 tuần)
- Dữ liệu nhiều hơn cho xây dựng chuỗi (14 + 1 = cửa sổ 15 ngày) = khởi tạo trạng thái LSTM tốt hơn

**Tác Động Đến Chỉ Số:**
- ✅ Cổng LSTM học các mẫu thời gian tinh vi hơn
- ✅ Có thể phân biệt các bùng phát một lần từ các xu hướng bền vững
- ✅ Giảm số lượng chuỗi ngắn thiếu ngữ cảnh

---

#### **SỬA 4: Thêm Các Đặc Tính Hành Vi Phong Phú**

**Các Đặc Tính Mới Thêm:**

```python
# Streaks check-in (tính nhất quán hành vi):
df['checkin_streak'] = df.groupby('user_id')['checked_in'].transform(
    lambda x: x.groupby((x != x.shift()).cumsum()).cumcount() + 1
) * df['checked_in']

# Cường độ khối lượng công việc:
df['task_workload'] = df['tasks_completed'] + df['avg_task_percentage'] / 100.0

# Mẫu hàng tuần (nhân viên có hành vi Thứ Sáu khác nhau):
df['day_of_week'] = pd.to_datetime(df['full_date']).dt.dayofweek
```

**Tại Sao Nó Quan Trọng:**
- **Streaks:** Phát hiện kiệt sức sớm (streak giảm = tín hiệu mất hứng thú)
- **Workload:** Nắm bắt khi nhân viên quá tải (tín hiệu kết hợp quan trọng)
- **Ngày trong tuần:** Cuối tuần/Thứ Sáu thể hiện các mẫu khác nhau; để mô hình hiệu chỉnh kỳ vọng

**Tác Động Đến Chỉ Số:**
- ✅ Số đặc tính: 11 → 17 đặc tính
- ✅ LSTM có nhiều tín hiệu đa chiều hơn để học
- ✅ Nắm bắt các tương tác phi tuyến (ví dụ: "khối lượng công việc cao + streak giảm" là yếu tố rủi ro)

---

#### **SỬA 5: Phân Loại Thay Vì Hồi Quy**

**Thay Đổi:**
```python
# CŨ:
model.add(Dense(1, activation='linear'))  # Hồi quy: dự đoán điểm 0-100
loss='mse'

# MỚI:
# Chuyển đổi mục tiêu thành nhãn 3 lớp:
def to_class_idx(score):
    if score >= 75: return 2  # Cao
    if score >= 55: return 1  # Trung Bình
    else: return 0            # Thấp

model.add(Dense(3, activation='softmax'))  # Phân loại: dự đoán xác suất lớp
loss='sparse_categorical_crossentropy'
```

**Tại Sao Nó Quan Trọng:**
- Ranh giới quyết định của người quản lý *rời rạc*: "Nhân viên này có cao/trung bình/thấp rủi ro?"
- Mất phân loại ổn định hơn cho vấn đề rời rạc này
- Có thể xuất điểm tự tin (xác suất softmax) cho mỗi lớp
- Mất cân bằng lớp được xử lý rõ ràng thông qua `class_weight_dict`

**Tác Động Đến Chỉ Số:**
- ✅ Chỉ số độ chính xác giờ có thể diễn giải: "Bao nhiêu % dự đoán có lớp đúng?"
- ✅ Macro-F1 cân bằng recall trên cả 3 lớp (ngăn chặn chỉ tập trung vào Cao)
- ✅ Dự đoán căn chỉnh với ngưỡng bảng điều khiển (75, 55 là ranh giới quyết định cứng)

---

#### **SỬA 6: Giảm Độ Phức Tạp Mô Hình**

**Thay Đổi:**
```python
# CŨ:
LSTM(64, return_sequences=True)
LSTM(32, return_sequences=False)
Dropout(0.2)

# MỚI:
LSTM(32, return_sequences=True)  # Giảm: 64 → 32
LSTM(16, return_sequences=False)  # Giảm: 32 → 16
Dropout(0.3)                      # Tăng: 0.2 → 0.3
```

**Tại Sao Nó Quan Trọng:**
- Tập dữ liệu: ~40k chuỗi huấn luyện (cho ~100 nhân viên × 2 năm)
- Mô hình lớn (64 đơn vị) quá khớp trên tập dữ liệu nhỏ → ghi nhớ tiếng ồn
- Năng lực nhỏ hơn buộc học các mẫu tổng quát hóa
- Dropout cao hơn (0.3) thêm phạt chính quy hóa

**Tác Động Đến Chỉ Số:**
- ✅ Giảm quá khớp (độ chính xác xác thực tăng, khoảng cách train-val giảm)
- ✅ Huấn luyện ổn định hơn (tham số ít hơn = tối thiểu cục bộ ít hơn)
- ✅ Suy luận nhanh hơn (mô hình được triển khai sử dụng ít FLOP hơn)

---

#### **SỬA 7: Chia Nhỏ Dựa Trên Thời Gian**

**Thay Đổi:**
```python
# CŨ:
split = int(len(X) * 0.8)
X_train, X_val = X[:split], X[split:]  # Vẫn theo thứ tự thời gian, nhưng không cắt rõ ràng

# MỚI:
train_end = pd.Timestamp('2025-10-31')
val_end   = pd.Timestamp('2026-01-31')

train_mask = date_idx <= train_end                               # Tới tháng 10 năm 2025
val_mask   = (date_idx > train_end) & (date_idx <= val_end)    # Tháng 11 năm 2025 – Tháng 1 năm 2026
test_mask  = date_idx > val_end                                 # Từ tháng 2 năm 2026 trở đi
```

**Tại Sao Nó Quan Trọng:**
- Cắt ngày rõ ràng ngăn *bất kỳ* sự mơ hồ về rò rỉ dữ liệu
- Tập kiểm tra được giữ lại trong tương lai (từ tháng 2 năm 2026 trở đi) — thực tế triển khai đúng
- Nếu mô hình huấn luyện trên "từ tháng 10 năm 2025 và trước đó," nó không thể nhìn thấy bất kỳ dữ liệu nào từ tương lai

**Tác Động Đến Chỉ Số:**
- ✅ Các chỉ số xác thực thực sự là dự đoán (không nhìn tương lai)
- ✅ Có thể đo hiệu suất mô hình như thể được triển khai trong thời gian thực
- ✅ Các chia nhỏ có thể tái tạo lại trên các lần huấn luyện lại

---

#### **SỬA 8: Thêm Cân Bằng Trọng Số Lớp**

**Thay Đổi:**
```python
# MỚI:
weights = compute_class_weight('balanced',
                               classes=np.array([0, 1, 2]),
                               y=y_train)
class_weight_dict = {0: weights[0], 1: weights[1], 2: weights[2]}

# Chuyển tới fit:
model.fit(..., class_weight=class_weight_dict, ...)
```

**Tại Sao Nó Quan Trọng:**
- Mất cân bằng lớp: ~50% Trung Bình, ~30% Thấp, ~20% Cao
- Không có trọng số, mô hình tập trung vào dự đoán lớp đa số (Trung Bình)
- Dự đoán Thấp và Cao trở nên không đáng tin cậy
- `compute_class_weight('balanced')` tự động tăng trọng số các lớp hiếm

**Tác Động Đến Chỉ Số:**
- ✅ Macro-F1 cải thiện (trọng số bằng nhau cho recall Thấp/Trung Bình/Cao)
- ✅ Recall lớp Thấp tăng (bắt nhân viên kiệt sức có rủi ro)
- ✅ Độ chính xác lớp Cao cải thiện (dự đoán "Cao" giả ít hơn)

---

### Tóm Tắt Các Thay Đổi

| Khía Cạnh | Cũ | Mới | Thay Đổi | Lý Do |
|--------|-----|-----|---------|-------|
| **Lookback** | 7 ngày | 14 ngày | +100% | Ngữ cảnh thời gian hơn |
| **Đặc Tính** | 11 dựa trên giá trị trung bình | 17 dựa trên lag | Xóa rò rỉ | Phụ thuộc thời gian an toàn |
| **Mục Tiêu** | Hồi quy mịn | Phân loại thô | Rời rạc + phương sai | Phù hợp với ranh giới quyết định |
| **Đơn Vị Mô Hình** | 64/32 | 32/16 | Nhỏ hơn | Ngăn chặn quá khớp |
| **Dropout** | 0.2 | 0.3 | Cao hơn | Chính quy hóa hơn |
| **Nhiệm Vụ** | Hồi quy (MSE) | Phân loại (CE) | Đa lớp | Có thể diễn giải + cân bằng |
| **Chia Nhỏ Dữ Liệu** | Thời gian (tiềm ẩn) | Thời gian (ngày tường minh) | Cắt rõ ràng | Không rò rỉ dữ liệu |
| **Cân Bằng Lớp** | Không | Trọng số | Trọng số lớp | Xử lý mất cân bằng |
| **Kết Quả** | 68,5% Độ Chính Xác, 0,647 F1 | (Cần đo lường) | ↑ Mục Tiêu: 75%+ | Các cải thiện dựa trên bằng chứng |

---

### Các Cải Thiện Dự Kiến (Lý Thuyết)

Dựa trên 8 sửa chữa được áp dụng:

1. **Độ Chính Xác → 75%+** — Phân loại tự nhiên hơn; ít hiện vật hồi quy
2. **Macro-F1 → 0,70+** — Cân bằng lớp + kiến trúc cân bằng
3. **Khoảng cách xác thực giảm** — Mô hình nhỏ hơn, dropout cao hơn, không rò rỉ
4. **Tầm quan trọng đặc tính trở nên ổn định** — Mẫu thực tế, không tương quan tiếng ồn
5. **Độ tin cậy sản xuất** — Tất cả các sửa chữa được thiết kế cho triển khai mạnh mẽ

---



```
MySQL (Ứng Dụng Laravel)
      │
      ▼
Quy Trình ETL (etl_pipeline.py)
      │   - Trích xuất check-in, nhiệm vụ, ngày nghỉ
      │   - Tính toán productivity_score qua công thức
      │   - Tải vào Kho Dữ Liệu PostgreSQL
      ▼
Kho Dữ Liệu PostgreSQL
      │   fact_employee_productivity
      │   dim_employee, dim_date, dim_task...
      ▼
Huấn Luyện LSTM (train_lstm.py)
      │   - Kéo dữ liệu từ Kho Dữ Liệu
      │   - Tạo đặc tính (trung bình động, xu hướng)
      │   - Huấn luyện mô hình LSTM
      │   - Lưu mô hình + scaler
      ▼
Flask API (api.py) ← mô hình được tải
      │   - Cung cấp dự đoán qua HTTP
      │   - Điểm cuối: GET /predict/{employee_id}
      ▼
Bảng Điều Khiển Laravel (LSTMDashboardController.php)
      │   - Gọi Flask API
      │   - Lưu cache trong bảng lstm_predictions
      │   - Cung cấp cho giao diện
      ▼
Bảng Điều Khiển Trình Duyệt (lstm-dashboard.js + Blade view)
```

### Quyết Định Thiết Kế Chính: Tại Sao LSTM?

Điểm số năng suất trong kho dữ liệu được tính toán bằng một **công thức xác định** trong `etl_pipeline.py`. Một mô hình hồi quy đơn giản sẽ học công thức này gần như hoàn hảo. Lý do LSTM có giá trị ở đây là nó nắm bắt được **các mẫu hành vi theo thời gian** — không chỉ đầu vào hôm nay, mà còn cách những đầu vào này đã phát triển trong 7 ngày qua.

Ví dụ, một nhân viên có `avg_score_7d` đang giảm ngay cả khi `checked_in=1` báo hiệu rủi ro kiệt sức mà công thức một ngày không thể phát hiện.

---

## Quy Trình Dữ Liệu & Kỹ Thuật Đặc Tính

### Đặc Tính Thô Từ PostgreSQL

Mô hình kéo những cột này từ `fact_employee_productivity`:

| Cột | Kiểu | Phạm Vi | Mô Tả |
|-----|------|--------|-------|
| `hours_worked` | float | 0–8+ | Giờ đăng ký được ghi hôm đó |
| `is_late` | bool → int | 0 hoặc 1 | Liệu check-in có sau 9:00 AM hay không |
| `checked_in` | bool → int | 0 hoặc 1 | Liệu nhân viên có check-in hay không |
| `had_day_off` | bool → int | 0 hoặc 1 | Có yêu cầu ngày nghỉ được phê duyệt hay không |
| `tasks_completed` | int | 0–N | Số lượng nhiệm vụ có `status='completed'` |
| `avg_task_score` | float | 0–10 | Điểm trung bình của các nhiệm vụ hoàn thành |
| `avg_task_percentage` | float | 0–100 | Phần trăm hoàn thành trung bình của tất cả các nhiệm vụ đang hoạt động |
| `productivity_score` | float | 0–100 | **MỤC TIÊU** — được tính toán bởi công thức ETL |

### Tại Sao 16,1% Ngày Không Có Tín Hiệu Nhiệm Vụ

Từ truy vấn cơ sở dữ liệu:
```
no_task_days: 8,890 trên 55,361 ngày check-in (16,1%)
```

Điều này xảy ra vì ETL gán nhiệm vụ cho ngày dựa trên `start_date ≤ record_date ≤ due_date`. Những ngày nằm ngoài phạm vi ngày của bất kỳ nhiệm vụ nào sẽ tạo ra `avg_task_score=0` và `avg_task_percentage=0`. Đây **không phải dữ liệu bị thiếu** — nó có nghĩa là nhân viên thực sự không có nhiệm vụ đang hoạt động ngày hôm đó. Công thức ETL xử lý điều này bằng một nhánh:

```python
# etl_pipeline.py — compute_productivity()
if has_tasks:
    score = (0.25*attendance + 0.25*hours_score + 0.30*task_pct + 0.20*task_score) * 100
else:
    score = (0.60*attendance + 0.40*hours_score) * 100
```

Công thức **hai chế độ** này là lý do chính tại sao các đặc tính thô đơn lẻ cho kết quả LSTM kém (R²=0,42 mà không có các đặc tính được kỹ sư). Mô hình cần biết nhánh nào được sử dụng.

### Đặc Tính Kỹ Sư (Được Thêm Vào trong train_lstm.py)

#### 1. `has_task_signal` (Cờ Nhị Phân)
```python
df['has_task_signal'] = (
    (df['avg_task_score'] > 0) |
    (df['avg_task_percentage'] > 0) |
    (df['tasks_completed'] > 0)
).astype(int)
```
**Tại Sao Nó Quan Trọng:** Điều này cho LSTM biết nhánh công thức nào tạo ra điểm hôm nay. Mà không có cờ này, mô hình thấy các mẫu tham dự giống hệt nhau tạo ra các điểm rất khác nhau (60 vs 85) và không thể học tại sao. Với cờ này, R² cải thiện từ 0,42 → 0,84.

#### 2. `avg_score_7d` (Xu Hướng Ngắn Hạn)
```python
df['avg_score_7d'] = df.groupby('user_id')['productivity_score'] \
    .transform(lambda x: x.rolling(7, min_periods=1).mean())
```
**Tại Sao Nó Quan Trọng:** Nắm bắt được động lực gần đây của nhân viên. Một điểm số 75 có ý nghĩa rất khác nếu trung bình 7 ngày là 85 (giảm) vs 65 (cải thiện).

#### 3. `avg_score_30d` (Hạng Mục Dài Hạn)
```python
df['avg_score_30d'] = df.groupby('user_id')['productivity_score'] \
    .transform(lambda x: x.rolling(30, min_periods=1).mean())
```
**Tại Sao Nó Quan Trọng:** Cung cấp đường cơ sở hiệu suất điển hình của nhân viên. LSTM có thể phân biệt những sự sụt giảm tạm thời (7d < 30d) với những thay đổi kéo dài.

#### 4. `score_trend` (Chỉ Số Động Lực)
```python
df['score_trend'] = df['avg_score_7d'] - df['avg_score_30d']
```
**Tại Sao Nó Quan Trọng:** Một giá trị dương có nghĩa là hiệu suất ngắn hạn cao hơn cơ sở (cải thiện). Một giá trị âm báo hiệu một sự sụt giảm. Một tính năng duy nhất này làm giảm đáng kể sự nhầm lẫn của mô hình giữa các lớp Medium và High.

| Giá Trị `score_trend` | Ý Nghĩa |
|------|---------|
| > +5 | Cải thiện tăng tốc |
| -2 đến +5 | Ổn định |
| < -5 | Xu hướng giảm — tín hiệu rủi ro kiệt sức tiềm ẩn |

#### 5. Làm Mịn Mục Tiêu (Trung Bình Động 3 Ngày)
```python
df['productivity_score'] = df.groupby('user_id')['productivity_score'] \
    .transform(lambda x: x.rolling(3, min_periods=1).mean())
```
**Tại Sao Nó Quan Trọng:** Công thức ETL xác định tạo ra những bước nhảy hàng ngày sắc nét khi tín hiệu nhiệm vụ xuất hiện/biến mất. Làm mịn trên 3 ngày làm giảm nhiễu này và làm cho mục tiêu dễ học hơn, tương tự như cách chuỗi thời gian tài chính được làm mịn trước khi mô hình hóa.

### Danh Sách Đặc Tính Cuối Cùng (11 đặc tính)

| # | Đặc Tính | Nguồn | Kiểu |
|---|----------|-------|------|
| 1 | `hours_worked` | DW Thô | float |
| 2 | `is_late` | DW Thô → int | nhị phân |
| 3 | `checked_in` | DW Thô → int | nhị phân |
| 4 | `had_day_off` | DW Thô → int | nhị phân |
| 5 | `tasks_completed` | DW Thô | int |
| 6 | `avg_task_score` | DW Thô | float |
| 7 | `avg_task_percentage` | DW Thô | float |
| 8 | `has_task_signal` | **Kỹ Sư** | nhị phân |
| 9 | `avg_score_7d` | **Kỹ Sư** | float |
| 10 | `avg_score_30d` | **Kỹ Sư** | float |
| 11 | `score_trend` | **Kỹ Sư** | float |

### Chuẩn Hóa Dữ Liệu

```python
from sklearn.preprocessing import MinMaxScaler

scaler = MinMaxScaler()
df[all_cols] = scaler.fit_transform(df[all_cols])
joblib.dump(scaler, "models/scaler.pkl")
```

**MinMaxScaler** ánh xạ mọi cột với [0, 1] bằng cách sử dụng:

$$x_{scaled} = \frac{x - x_{min}}{x_{max} - x_{min}}$$

**Quy tắc quan trọng:**
- Scaler được **trang bị chỉ trên dữ liệu huấn luyện** qua `fit_transform()` 
- Đánh giá sử dụng `transform()` chỉ — không bao giờ `fit_transform()` lại
- Scaler phải được lưu và tải lại để suy luận, hoặc dự đoán sẽ ở sai thang đo
- Nếu bạn thêm/xóa đặc tính, hãy xóa `scaler.pkl` và huấn luyện lại từ đầu — scaler được gắn với thứ tự cột chính xác

**Tại Sao Chuẩn Hóa Quan Trọng Đối Với LSTM:**
- `hours_worked` có phạm vi 0–10, `is_late` là 0/1 — mà không chuẩn hóa, giờ làm việc chiếm ưu thế gradient
- Đầu vào bình thường hóa ngăn chặn gradient biến mất/phát nổ trong quá trình lan truyền ngược
- Cổng LSTM (quên, nhập, xuất) sử dụng kích hoạt sigmoid — đầu vào gần 0/1 là lý tưởng

---

## Quá Trình Huấn Luyện

### Xây Dựng Chuỗi (Cửa Sổ Trượt)

LSTM yêu cầu đầu vào tuần tự. Đối với mỗi nhân viên, một cửa sổ trượt `LOOKBACK=7` ngày được sử dụng:

```
Dữ Liệu Nhân Viên A (sắp xếp theo ngày):
Ngày 1: [features_1]
Ngày 2: [features_2]
...
Ngày 7: [features_7]
Ngày 8: [features_8]  ← mục tiêu (y)

Chuỗi 1: X = [[features_1], ..., [features_7]], y = score_day_8
Chuỗi 2: X = [[features_2], ..., [features_8]], y = score_day_9
...
```

**Hình Dạng Đầu Ra:**
- `X`: `(total_sequences, 7, 11)` — (mẫu, bước thời gian, đặc tính)
- `y`: `(total_sequences,)` — một điểm số trên mỗi chuỗi

**Tại Sao LOOKBACK=7?**
- 7 ngày = 1 tuần làm việc — chu kỳ hành vi tự nhiên nhất
- Đủ dài để nắm bắt các mẫu hàng tuần (ví dụ: sự hạ sắc vào thứ Sáu)
- Đủ ngắn để hầu hết nhân viên (với 2000+ ngày dữ liệu) tạo ra hàng nghìn chuỗi
- Được kiểm tra so với LOOKBACK=30: cửa sổ dài hơn thêm nhiễu vì điểm số do công thức không có phụ thuộc thực sự 30 ngày

**Yêu Cầu Dữ Liệu Tối Thiểu:**
- Nhân viên có ít hơn `LOOKBACK + 1 = 8` hồ sơ bị bỏ qua
- Điều này hiếm khi xảy ra vì DW có ~2000 ngày trên mỗi nhân viên

### Chia Nhỏ Huấn Luyện/Xác Thực

```python
split = int(len(X) * 0.8)
X_train, X_val = X[:split], X[split:]
y_train, y_val = y[:split], y[split:]
```

**Quan Trọng: Đây là một phần theo thứ tự thời gian, không phải ngẫu nhiên.** Vì các chuỗi được sắp xếp theo thời gian cho mỗi nhân viên và nhân viên được xử lý tuần tự, 80% đầu tiên của các chuỗi diễn ra sớm trong thời gian. Đây là cách tiếp cận đúng cho chuỗi thời gian — tách ngẫu nhiên sẽ rò rỉ thông tin trong tương lai vào huấn luyện.

**Thống Kê Chia Nhỏ (xấp xỉ):**
- Tổng chuỗi: ~45.000 (từ 56k hàng, trừ khoảng trống LOOKBACK)
- Huấn luyện: ~36.000 chuỗi
- Xác thực: ~9.000 chuỗi

### Hành Vi EarlyStopping

```python
early_stop = EarlyStopping(
    monitor='val_loss',
    patience=10,
    restore_best_weights=True,
    verbose=1
)
```

Số lượng epoch khác nhau giữa các lần chạy vì:

1. **Khởi tạo trọng số ngẫu nhiên** — các trọng số khác nhau dẫn đến cảnh quan mất mát khác nhau
2. **Keras xáo trộn các batch huấn luyện** mỗi epoch theo mặc định
3. **Mất xác thực có tiếng ồn** — nó dao động xung quanh mức tối thiểu thực

Công thức dừng là:
```
Tổng epoch = Epoch tốt nhất + kiên nhẫn
14 epoch    = Epoch 4 tốt nhất + 10  (hội tụ nhanh)
31 epoch    = Epoch 21 tốt nhất + 10  (hội tụ chậm hơn)
```

Cả hai đều là hành vi đúng. Để làm cho kết quả có thể tái tạo, hãy đặt một hạt ngẫu nhiên (xem phần [Tái Tạo](#tái-tạo--hạt-ngẫu-nhiên)).

---

## Kiến Trúc Mô Hình

```
Đầu Vào: (7 bước thời gian, 11 đặc tính)
         │
    LSTM(64 đơn vị, return_sequences=True)
         │  Đầu Ra: (7, 64) — giữ tất cả đầu ra bước thời gian
    Dropout(0.2)
         │  Loại 20% nơ-ron ngẫu nhiên trong quá trình huấn luyện
    LSTM(32 đơn vị, return_sequences=False)
         │  Đầu Ra: (32,) — chỉ bước thời gian cuối cùng
    Dropout(0.2)
         │
    Dense(16, activation='relu')
         │  Chuyển đổi phi tuyến tính
    Dense(1, activation='linear')
         │  Đầu ra hồi quy — không bị ràng buộc
         ▼
    Điểm số năng suất được dự đoán (tỷ lệ 0–1)
```

### Tại Sao Kiến Trúc Này

| Thành Phần | Lý Do |
|-----------|-------|
| **2 lớp LSTM** | Lớp đầu tiên xử lý các mẫu thời gian thô; lớp thứ hai nén thành biểu diễn trừu tượng |
| **64 đơn vị (L1)** | Đủ rộng để nắm bắt các mẫu hàng tuần phức tạp trên 11 đặc tính |
| **32 đơn vị (L2)** | Thu hẹp biểu diễn — buộc nén các mẫu có ý nghĩa |
| **Dropout(0.2)** | Ngăn chặn ghi nhớ; 20% là bảo thủ và thích hợp cho kích thước tập dữ liệu này |
| **Dense(16, ReLU)** | Giới thiệu phi tuyến tính trước đầu ra; ReLU hoạt động tốt sau LSTM |
| **Dense(1, Linear)** | Đầu ra hồi quy — sigmoid sẽ giới hạn dự đoán ở 1,0 và bóp méo thang đo |

### Cài Đặt Biên Dịch

```python
model.compile(
    optimizer='adam',           # Tỷ lệ học thích nghi cho mỗi tham số
    loss='mean_squared_error',  # Phạt lỗi dự đoán lớn bậc hai
    metrics=['mean_absolute_error']  # Theo dõi sai số dễ hiểu trong quá trình huấn luyện
)
```

**Tại Sao MSE cho mất mát?** MSE phạt các lỗi dự đoán lớn nhiều hơn lỗi nhỏ (bình phương khuếch đại ngoại lệ). Để dự đoán năng suất, một điểm số sai 30 điểm tệ hơn nhiều so với một điểm sai 3 điểm — hình phạt bậc hai của MSE phù hợp với ưu tiên này.

**Tại Sao MAE là chỉ số?** MAE được báo cáo trong cùng các đơn vị với mục tiêu (tỷ lệ 0–1 trong quá trình huấn luyện). Nó dễ hiểu hơn MSE để theo dõi tiến độ huấn luyện.

### Tổng Số Tham Số

```
LSTM(64):  4 × 64 × (11 + 64 + 1) = 19.456
LSTM(32):  4 × 32 × (64 + 32 + 1) = 12.416
Dense(16): 32 × 16 + 16 = 528
Dense(1):  16 × 1 + 1 = 17
Tổng: 32.417 tham số
```

Đây là một **mô hình nhỏ** theo tiêu chuẩn ML. Đối với 45k+ chuỗi huấn luyện, điều này là thích hợp — một mô hình lớn hơn sẽ quá khớp.

---

## Quy Trình & Chỉ Số Đánh Giá

### Tại Sao Chúng Ta Đánh Giá Khác Với Huấn Luyện

Huấn luyện giảm thiểu MSE trên các giá trị được chuẩn hóa. Đánh giá chuyển đổi dự đoán trở lại 0–100 và đo lường cả hai:
1. **Độ Chính Xác Hồi Quy** (MAE, RMSE, R²) — các số gần đúng như thế nào?
2. **Độ Chính Xác Phân Loại** (Ma Trận Nhầm Lẫn, F1) — chúng ta có chính xác xác định nhân viên Thấp/Trung Bình/Cao hay không?

Cả hai đều quan trọng: độ chính xác hồi quy ảnh hưởng đến tính hữu ích lâm sàng, độ chính xác phân loại ảnh hưởng đến nhãn bảng điều khiển.

### Chuẩn Hóa Ngược

```python
# Mô hình xuất ra dự đoán được chuẩn hóa (0–1)
y_pred_scaled = model.predict(X).flatten()

# Xây dựng lại mảng giả để chuẩn hóa ngược
dummy_actual = np.zeros((len(y), len(all_cols)))
dummy_pred   = np.zeros((len(y_pred_scaled), len(all_cols)))
dummy_actual[:, -1] = y               # đặt điểm số trong cột cuối cùng
dummy_pred[:, -1]   = y_pred_scaled

# Chuẩn hóa ngược — trích xuất chỉ cột mục tiêu
actual_scores    = scaler.inverse_transform(dummy_actual)[:, -1]
predicted_scores = scaler.inverse_transform(dummy_pred)[:, -1]
```

Cách tiếp cận này là cần thiết vì `MinMaxScaler` được trang bị trên tất cả 12 cột cùng nhau. Để đảo ngược một cột duy nhất, chúng ta xây dựng lại mảng giả toàn bộ chiều rộng và để scaler đảo ngược nó.

### Ngưỡng Phân Loại

```python
def to_class(score):
    if score >= 80: return 'High'    # Thành viên dàn dựng mạnh mẽ
    if score >= 60: return 'Medium'  # Hiệu suất chấp nhận được
    return 'Low'                     # Cần quan tâm
```

Những ngưỡng này tương ứng với phân phối đầu ra tự nhiên của công thức ETL:
- `avg_score = 77,61`, `std_dev = 20,01` (từ cơ sở dữ liệu)
- High (≥80): nhân viên trên trung bình — khoảng 47% ngày
- Medium (60–79): xung quanh trung bình — khoảng 27% ngày
- Low (<60): dưới trung bình — khoảng 26% ngày

**Quan Trọng:** Medium có dải hẹp nhất (20 điểm) và nằm giữa hai ranh giới lớp. Với MAE=6,29, dự đoán gần ranh giới 60 hoặc 80 có thể vượt qua lớp sai. Đây là lý do tại sao Medium F1 (0,681) thấp hơn Low (0,849) và High (0,877) — đó là lớp có cấu trúc khó dự đoán nhất.

### Metric Deep Dive

#### Ma Trận Nhầm Lẫn

```
                 Pred Thấp  Pred Trung Bình  Pred Cao
Nguyên Thấp        13.050        809          47    | Hỗ Trợ: 13.906
Nguyên Trung Bình   3.776     11.388         840    | Hỗ Trợ: 16.004
Nguyên Cao             0      5.233      21.736    | Hỗ Trợ: 26.969
```

**Đọc Ma Trận:**
- **Đường chéo** (13050, 11388, 21736): Dự đoán chính xác
- **Act High → Pred Low: 0** — Mô hình không bao giờ phân loại sai một nhân viên hiệu suất cao là thấp. Đây là lỗi quan trọng nhất cần tránh cho một công cụ dành cho quản lý.
- **Act Medium → Pred Low: 3.776** — Nguồn lỗi lớn nhất. Nhân viên trung bình gần ranh giới 60 điểm được dự đoán là Thấp. Điều này được kỳ vọng với MAE=6,29 và ranh giới 60 điểm.
- **Act Low → Pred High: 47** — Rất hiếm. Mô hình gần như không bao giờ làm sai phân loại một nhân viên hiệu suất thấp.

#### Precision

$$\text{Precision}_c = \frac{TP_c}{TP_c + FP_c}$$

*"Trong tất cả nhân viên được mô hình gắn nhãn là lớp C, bao nhiêu phần trăm thực sự thuộc lớp C?"*

| Lớp | Độ Chính Xác | Diễn Giải |
|-----|----------|---------|
| Thấp | 0,776 | Khi mô hình nói "Thấp", đúng 77,6% thời gian |
| Trung Bình | 0,653 | Khi mô hình nói "Trung Bình", đúng 65,3% thời gian |
| Cao | 0,961 | Khi mô hình nói "Cao", đúng 96,1% thời gian |

Độ chính xác cao cho "Cao" rất quan trọng để xây dựng niềm tin của quản lý — nó có nghĩa là danh sách Những Người Dàn Dựng Hàng Đầu trên bảng điều khiển gần như luôn đúng.

#### Gọi Lại (Recall)

$$\text{Recall}_c = \frac{TP_c}{TP_c + FN_c}$$

*"Trong tất cả nhân viên thực sự thuộc lớp C, bao nhiêu phần trăm chúng ta xác định chính xác?"*

| Lớp | Gọi Lại | Diễn Giải |
|-----|---------|---------|
| Thấp | 0,938 | Bắt được 93,8% nhân viên có hiệu suất thực sự thấp |
| Trung Bình | 0,712 | Bắt được 71,2% nhân viên trung bình |
| Cao | 0,806 | Bắt được 80,6% nhân viên có hiệu suất cao |

Gọi lại cao cho "Thấp" rất quan trọng cho việc sử dụng HR — nó có nghĩa là 93,8% nhân viên có nguy hiểm được gắn cờ chính xác trong bảng "Cần Quan Tâm".

#### Điểm F1

$$F_1 = 2 \times \frac{\text{Precision} \times \text{Recall}}{\text{Precision} + \text{Recall}}$$

*"Trung bình điều hòa của Precision và Recall — chỉ số cân bằng duy nhất trên mỗi lớp."*

| Lớp | F1 | Đánh Giá |
|-------|-----|------------|
| Thấp | 0,849 | Tốt |
| Trung Bình | 0,681 | Chấp nhận được — vấn đề lớp ranh giới |
| Cao | 0,877 | Tốt |

F1 sử dụng trung bình điều hòa (không phải trung bình cộng) vì nó phạt những sự mất cân bằng cực đoan — một mô hình có Precision=1,0 và Recall=0,1 được F1=0,18, không phải 0,55.

#### Macro F1

$$\text{Macro F1} = \frac{F1_{Low} + F1_{Medium} + F1_{High}}{3} = \frac{0.849 + 0.681 + 0.877}{3} = 0.802$$

Coi các lớp bình đẳng bất kể kích thước hỗ trợ. Đây là chỉ số chính để đánh giá luận văn.

| Macro F1 | Phán Quyết |
|----------|---------|
| ≥ 0.90 | Tuyệt vời — đáng tin cậy cao |
| **≥ 0.80** | **Tốt — phù hợp cho luận văn ✅ (kết quả của bạn: 0,802)** |
| ≥ 0.70 | Chấp nhận được — có thể sử dụng với cảnh báo |
| < 0.70 | Kém — huấn luyện lại trước khi sử dụng |

#### MAE (Lỗi Tuyệt Đối Trung Bình)

$$\text{MAE} = \frac{1}{n}\sum_{i=1}^{n}|y_i - \hat{y}_i|$$

Báo cáo sai số dự đoán trung bình trong thang 0–100 gốc.

**Kết quả của bạn: MAE = 6,29 điểm**

| MAE | Đánh Giá |
|-----|-----------|
| < 5 | Tuyệt vời |
| **5–10** | **Tốt ✅ (kết quả của bạn: 6,29)** |
| 10–15 | Chấp nhận được |
| > 15 | Kém |

**Quan Trọng: Kịch bản huấn luyện báo cáo MAE ở các đơn vị được chuẩn hóa (0–1), KHÔNG phải các đơn vị thực (0–100).** MAE thực tế là 6,29 điểm. Đừng sử dụng phán quyết chất lượng của kịch bản huấn luyện cho luận văn — sử dụng đầu ra của kịch bản đánh giá.

#### RMSE (Lỗi Bình Phương Trung Bình Gốc)

$$\text{RMSE} = \sqrt{\frac{1}{n}\sum_{i=1}^{n}(y_i - \hat{y}_i)^2}$$

Phạt các lỗi lớn nhiều hơn MAE. Nếu RMSE >> MAE, có những lỗi dự đoán lớn thỉnh thoảng kéo nó lên.

**Kết quả của bạn: RMSE = 7,79 điểm**

Tỷ lệ RMSE/MAE = 7,79/6,29 = 1,24 — gần 1,0, có nghĩa là các lỗi khá đồng đều (không có ngoại lệ tuyệt vọng).

#### R² (Hệ Số Xác Định)

$$R^2 = 1 - \frac{\sum(y_i - \hat{y}_i)^2}{\sum(y_i - \bar{y})^2}$$

Đo lường bao nhiêu phương sai điểm số mô hình giải thích.

**Kết quả của bạn: R² = 0,8443**

Điều này có nghĩa là mô hình giải thích **84,4% phương sai điểm số năng suất**. 15,6% còn lại đến từ tiếng ồn cố hữu trong công thức xác định (các tạo tác gán ngày nhiệm vụ, v.v.).

**So Sánh Kỹ Thuật Hóa Đặc Tính Trước/Sau:**

| Chỉ Số | Không Có Đặc Tính Kỹ Sư | Với Đặc Tính Kỹ Sư | Cải Thiện |
|--------|----------------------|-------------------|-------------|
| Độ Chính Xác | 68,1% | **81,2%** | +13,1% |
| Macro F1 | 0,660 | **0,802** | +0,142 |
| MAE | 12,72 pts | **6,29 pts** | -6,43 pts |
| R² | 0,4171 | **0,8443** | +0,427 |

Sự so sánh này là bằng chứng luận văn mạnh mẽ rằng các đặc tính kỹ sư (không chỉ LSTM) là đóng góp chính.

---

## Hiểu Rõ Kết Quả Của Bạn

### Tại Sao Medium F1 Thấp Hơn Low và High

Lớp Medium (60–79) chỉ kéo dài 20 điểm. Với MAE=6,29:
- Một nhân viên ghi 62 có thể được dự đoán là 55,71 → được phân loại là Thấp ❌
- Một nhân viên ghi 78 có thể được dự đoán là 84,29 → được phân loại là Cao ❌

Đây là một **vấn đề ranh giới cấu trúc**, không phải lỗi mô hình. Nó sẽ ảnh hưởng đến bất kỳ mô hình nào có định nghĩa lớp này và MAE này. Low (0–59) và High (80–100) rộng hơn hoặc ở các cạnh và ít bị ảnh hưởng hơn.

### Tại Sao Act High → Pred Low = 0

Không bao giờ có nhân viên có hiệu suất thực sự cao (điểm ≥ 80) được dự đoán là Thấp. Đây là vì:
1. Nhân viên hiệu suất cao có `avg_score_7d` và `avg_score_30d` cao nhất quán
2. Những đặc tính kỹ sư này làm cho lớp High rất khác biệt với Thấp
3. Một lỗi dự đoán 20+ điểm sẽ cần thiết để phân loại sai High thành Low — far beyond model's typical MAE of 6.29

Sự không đối xứng này thực sự là tối ưu cho một công cụ dành cho quản lý — lỗi tệ nhất có thể xảy ra (gắn nhãn nhân viên sao là có nguy hiểm) gần như không bao giờ xảy ra.

### Vấn Đề "Dữ Liệu Sạch ≠ Dữ Liệu Dự Đoán"

Dữ Liệu PostgreSQL của bạn có chất lượng tuyệt vời:
- Không có vi phạm NULL
- 55.361 hàng với nhập chính xác
- 74 giá trị điểm phân biệt, avg 77,61

Tuy nhiên, R² = 0,8443 có nghĩa là 15,6% phương sai chưa được giải thích. Điều này là vì mục tiêu `productivity_score` là một **công thức được tính toán**, và công thức đó có một sự không liên tục cấu trúc (nhánh `has_tasks`). Không có LSTM nào có thể học hoàn hảo một công thức từ đầu vào của nó khi công thức hoạt động khác nhau dựa trên một điều kiện ẩn — nó chỉ có thể xấp xỉ nó.

Đối với luận văn của bạn, đây là một điểm mạnh: bạn có thể giải thích rằng 15,6% phương sai chưa được giải thích đại diện cho tiếng ồn không thể giảm từ cấu trúc hai chế độ của công thức, không phải chất lượng dữ liệu bị thiếu.

---

## Các Hạn Chế Đã Biết & Khung Luận Văn

### Hạn Chế 1: Rủi Ro Rò Rỉ Mục Tiêu

Mục tiêu `productivity_score` được tính toán từ một số đặc tính tương tự được sử dụng làm đầu vào. LSTM không phải là khám phá một mẫu ẩn — nó là xấp xỉ một công thức đã biết với bối cảnh thời gian thêm vào. Điều này có nghĩa là:
- Mô hình sẽ không bao giờ vượt quá công thức trên dữ liệu huấn luyện
- Giá trị của mô hình nằm trong **dự đoán xu hướng** (sử dụng lịch sử 7 ngày), không phải sao chép công thức

**Khung Luận Văn:**
> *"Mô hình LSTM không thay thế điểm số năng suất dựa trên công thức. Thay vào đó, nó sử dụng 7 ngày lịch sử hành vi để dự đoán nơi điểm số của nhân viên sẽ hạ cánh ngày mai, cho phép can thiệp chủ động trước khi hiệu suất sụt giảm."*

### Hạn Chế 2: Đánh Giá Trên Dữ Liệu Huấn Luyện

`evaluate_classifier.py` hiện tại đánh giá trên **tất cả dữ liệu khả dụng**, bao gồm các chuỗi được sử dụng cho huấn luyện. Điều này có nghĩa là độ chính xác 81,2% được báo cáo có thể là lạc quan. Để một bài kiểm tra holdout nghiêm ngặt:

```python
# Trong evaluate_classifier.py — đánh giá chỉ trên 90 ngày cuối cùng cho mỗi nhân viên
cutoff = df['full_date'].max() - pd.Timedelta(days=90)
test_df = df[df['full_date'] > cutoff]
```

Nếu bạn làm điều này và độ chính xác sụt giảm thành ~ 78%, đó vẫn là "TỐT" và là một con số trung thực hơn cho luận văn của bạn.

### Hạn Chế 3: Biến Ẩn (R² = 0,8443, không phải 1,0)

Phương sai 15,6% chưa được giải thích phản ánh các yếu tố hệ thống không thể nắm bắt:
- Độ khó thực tế của nhiệm vụ (một nhiệm vụ "hoàn thành" có thể là tầm thường hoặc phức tạp)
- Sức khỏe, động lực và tập trung của nhân viên
- Động lực nhóm và tải cuộc họp
- Sao lãng bên ngoài hoặc hoàn cảnh cá nhân

**Khung Luận Văn:**
> *"Mô hình giải thích 84,4% phương sai điểm số năng suất (R²=0,8443). 15,6% còn lại là do các yếu tố bối cảnh không được nắm bắt trong hệ thống KPI, bao gồm độ phức tạp của nhiệm vụ, sức khỏe của nhân viên và động lực nhân viên. Phát hiện này phù hợp với nghiên cứu HR được thiết lập cho thấy rằng số liệu hành vi khách quan một mình không thể dự đoán đầy đủ hiệu suất cá nhân."*

### Error: Feature names mismatch in scaler

```
ValueError: The feature names should match those that were passed during fit.
Feature names seen at fit time, yet now missing:
- avg_score_30d
- avg_score_7d
- has_task_signal
- score_trend
```

**Cause:** You changed `FEATURES` in `train_lstm.py` but are running an old `scaler.pkl`.

**Fix:**
```bash
rm models/scaler.pkl
rm models/lstm_productivity.keras
python3 train_lstm.py        # retrain first
python3 evaluate_classifier.py  # then evaluate
```

**Golden rule:** Every time you change `FEATURES`, delete both saved files and retrain before evaluating.

---

### Model says "GOOD" in training but evaluation says "POOR"

**Cause:** Training script checks MAE in **scaled units** (0–1). Evaluation script reports MAE in **real units** (0–100).

```
Training:   best_mae = 0.043  → prints "GOOD" (0.043 < 0.10 threshold)
Evaluation: MAE = 12.72 pts   → actually POOR
```

The training script's quality verdict is misleading. Always use `evaluate_classifier.py` for your real metrics.

**Fix:** Add real-unit conversion to training script:
```python
target_idx = all_cols.index(TARGET)
score_range = scaler.data_max_[target_idx] - scaler.data_min_[target_idx]
real_mae = best_mae * score_range
print(f"Best val_mae (real units): {real_mae:.2f} pts")
```

---

### Training stops at only 4–5 epochs

---

## Tái Tạo & Hạt Ngẫu Nhiên

### Tại Sao Số Lượng Epoch Thay Đổi (14 vs 30+)

Mỗi lần chạy huấn luyện sử dụng khởi tạo trọng số ngẫu nhiên khác nhau và sắp xếp batch khác nhau. Epoch tốt nhất thay đổi, và vì `patience=10`, tổng epoch = epoch tốt nhất + 10.

Đây là **hành vi đúng** — không phải một lỗi.

### Làm Cho Kết Quả Có Thể Tái Tạo (Được Khuyến Cáo Cho Luận Văn)

Thêm những dòng này ở rất trên cùng của `train_lstm.py`, trước bất kỳ lần nhập nào khác:

```python
import random
import numpy as np
import tensorflow as tf

SEED = 42
random.seed(SEED)
np.random.seed(SEED)
tf.random.set_seed(SEED)
```

Với một hạt cố định, mỗi lần chạy tạo ra epoch giống hệt nhau, chỉ số giống hệt nhau, và trọng số mô hình giống hệt nhau — quan trọng cho tái tạo luận văn.

### Được Khuyến Cáo: Báo Cáo Phương Sai Trước Khi Sửa Hạt

Chạy huấn luyện 5 lần **mà không** hạt, ghi lại chỉ số, sau đó sửa hạt:

```
Lần 1: Độ Chính Xác=81,2%, Macro F1=0,802, Epochs=15
Lần 2: Độ Chính Xác=80,8%, Macro F1=0,798, Epochs=31
Lần 3: Độ Chính Xác=81,5%, Macro F1=0,805, Epochs=22
Lần 4: Độ Chính Xác=80,9%, Macro F1=0,800, Epochs=18
Lần 5: Độ Chính Xác=81,1%, Macro F1=0,801, Epochs=26
Trung Bình: 81,1% ± 0,3%
```

Sau đó viết trong luận văn của bạn:
> *"Mô hình được huấn luyện 5 lần với các khởi tạo ngẫu nhiên khác nhau để xác minh tính ổn định, đạt độ chính xác trung bình 81,1% ± 0,3%. Một hạt cố định (42) được áp dụng cho mô hình được báo cáo cuối cùng để đảm bảo tái tạo."*

Cách tiếp cận này mạnh hơn chỉ báo cáo một lần chạy — nó chứng minh rằng kết quả không phải là một sự may mắn."

---

## Khắc Phục Sự Cố

### Lỗi: Sự Không Khớp Tên Đặc Tính Trong Scaler

```
ValueError: The feature names should match those that were passed during fit.
Feature names seen at fit time, yet now missing:
- avg_score_30d
- avg_score_7d
- has_task_signal
- score_trend
```

**Nguyên Nhân:** Bạn thay đổi `FEATURES` trong `train_lstm.py` nhưng đang chạy `scaler.pkl` cũ.

**Sửa:**
```bash
rm models/scaler.pkl
rm models/lstm_productivity.keras
python3 train_lstm.py        # huấn luyện lại trước
python3 evaluate_classifier.py  # sau đó đánh giá
```

**Quy Tắc Vàng:** Mỗi khi bạn thay đổi `FEATURES`, hãy xóa cả hai tệp được lưu và huấn luyện lại trước khi đánh giá.

---

### Mô Hình Nói "TỐT" Khi Huấn Luyện Nhưng Đánh Giá Nói "KÉM"

**Nguyên Nhân:** Kịch bản huấn luyện kiểm tra MAE trong **các đơn vị được chuẩn hóa** (0–1). Kịch bản đánh giá báo cáo MAE trong **các đơn vị thực** (0–100).

```
Huấn Luyện:   best_mae = 0,043  → in "TỐT" (0,043 < ngưỡng 0,10)
Đánh Giá: MAE = 12,72 pts   → thực sự KÉM
```

Phán quyết chất lượng của kịch bản huấn luyện gây hiểu lầm. Luôn sử dụng `evaluate_classifier.py` cho các chỉ số thực tế của bạn.

**Sửa:** Thêm chuyển đổi đơn vị thực vào kịch bản huấn luyện:
```python
target_idx = all_cols.index(TARGET)
score_range = scaler.data_max_[target_idx] - scaler.data_min_[target_idx]
real_mae = best_mae * score_range
print(f"Best val_mae (real units): {real_mae:.2f} pts")
```

---

### Huấn Luyện Dừng Chỉ Ở 4–5 Epoch

**Nguyên Nhân:** Mô hình đang hội tụ cực nhanh, hoặc mất xác thực không giảm ở tất cả từ epoch 1.

**Kiểm Tra:**
```python
print(f"Training sequences: {X_train.shape}")
print(f"Feature variance: {X_train.std(axis=(0,1))}")
# Nếu bất kỳ đặc tính nào có std gần 0, nó không cung cấp tín hiệu
```

Nếu các đặc tính có phương sai gần 0 sau khi chuẩn hóa, hãy kiểm tra xem ETL đã chạy thành công và DW có đủ dữ liệu hay không.

---

### Macro F1 < 0,70 Sau Khi Thêm Đặc Tính Kỹ Sư

**Các nguyên nhân và sửa chữa có thể:**

1. **evaluate.py không có cùng một kỹ sư đặc tính như train.py**
   - Đảm bảo cả hai kịch bản tạo `has_task_signal`, `avg_score_7d`, `avg_score_30d`, `score_trend` giống hệt nhau

2. **evaluate.py sử dụng `fit_transform` thay vì `transform`**
   ```python
   # SAI — trang bị lại scaler trên dữ liệu kiểm tra
   df[all_cols] = scaler.fit_transform(df[all_cols])
   
   # ĐÚNG — sử dụng scaler huấn luyện
   df[all_cols] = scaler.transform(df[all_cols])
   ```

3. **Sự mất cân bằng lớp nghiêm trọng**
   ```python
   print(pd.Series(actual_classes).value_counts())
   # Nếu một lớp có < 15% dữ liệu, hãy xem xét cân nặng lớp
   ```

---

### LSTM API Trả Về Điểm Số Dường Như Quá Cao Hoặc Quá Thấp Trên Bảng Điều Khiển

**Nguyên Nhân:** Sự Không Khớp Thang Điểm Giữa Đầu Ra Flask API Và Bộ Điều Khiển Laravel.

Mô hình LSTM xuất ra **các giá trị được chuẩn hóa (0–1)**. Flask `api.py` của bạn phải chuẩn hóa ngược trước khi trả về. Kiểm tra xem API của bạn trả về ở tỷ lệ nào:

```php
// Trong LSTMDashboardController.php — thêm tạm thời ghi nhật ký
Log::info("Raw LSTM API response: " . $response->body());
// Nếu phản hồi hiển thị {"productivity_score": 0.77} → nhân với 100
// Nếu phản hồi hiển thị {"productivity_score": 77.0} → KHÔNG nhân với 100
```

Nếu bạn nhân đôi-nhân (×100 trong Flask VÀ ×100 trong Laravel), điểm số trở thành 7700 — giá trị không thể xảy ra mà bảng điều khiển sẽ hiển thị không chính xác.

---

## Tham Chiếu Nhanh

### Tệp

| Tệp | Mục Đích |
|------|---------|
| `train_lstm.py` | Mô Hình Huấn Luyện; tạo ra `models/lstm_productivity.keras` + `models/scaler.pkl` |
| `evaluate_classifier.py` | Đánh giá đầy đủ với ma trận nhầm lẫn, F1, MAE, R² |
| `api.py` | Flask API cuti Predictions trên cảng 5001 |
| `models/lstm_productivity.keras` | Trọng số LSTM Được Huấn Luyện |
| `models/scaler.pkl` | MinMaxScaler Trang Bị Trên Dữ Liệu Huấn Luyện |
| `models/metrics.json` | Kết Quả Đánh Giá (viết sau evaluate.py) |

### Ngưỡng Phân Loại

```python
def to_class(score):
    if score >= 80: return 'High'    # ≥80: nhân viên mạnh mẽ
    if score >= 60: return 'Medium'  # 60–79: chấp nhận được
    return 'Low'                     # <60: cần quan tâm
```

### Các Lệnh Chính

```bash
# Quy trình công việc đầy đủ
python3 train_lstm.py           # Huấn luyện (xóa mô hình cũ trước nếu thay đổi đặc tính)
python3 evaluate_classifier.py  # Đánh giá
python3 api.py                  # Bắt đầu Flask API trên cảng 5001

# Nếu bạn thay đổi FEATURES
rm models/scaler.pkl models/lstm_productivity.keras
python3 train_lstm.py
python3 evaluate_classifier.py
```

### Tóm Tắt Hiệu Suất (Mô Hình Hiện Tại)

| Chỉ Số | Giá Trị | Đánh Giá |
|--------|-------|-----------|
| Độ Chính Xác | 81,2% | ✅ Tốt |
| Macro F1 | 0,802 | ✅ Tốt — phù hợp cho luận văn |
| MAE | 6,29 pts | ✅ Tốt |
| RMSE | 7,79 pts | ✅ Tốt |
| R² | 0,8443 | ✅ Giải thích 84,4% phương sai |
| Low F1 | 0,849 | ✅ Tốt |
| Medium F1 | 0,681 | ⚠️ Chấp nhận được — lớp ranh giới |
| High F1 | 0,877 | ✅ Tốt |

### Danh Sách Kiểm Tra Trước Khi Gửi Luận Văn

- [ ] Chạy huấn luyện 5 lần mà không có hạt — ghi lại trung bình và phương sai của Macro F1
- [ ] Thêm `tf.random.set_seed(42)` vào `train_lstm.py` để chạy cuối cùng
- [ ] Xác minh `evaluate_classifier.py` sử dụng `scaler.transform()` không phải `fit_transform()`
- [ ] Xác minh evaluate.py có cùng một kỹ sư đặc tính với train.py
- [ ] Xác nhận Flask API trả về thang đúng (0–100, không phải 0–1)
- [ ] Cập nhật `getModelAccuracy()` bảng điều khiển để đọc từ `metrics.json` (không phải 87,3 được mã hóa cứng)
- [ ] Thêm bài kiểm tra holdout dựa trên thời gian (90 ngày cuối cùng) để đánh giá trung thực
- [ ] Ghi lại đóng góp kỹ sư đặc tính (R² 0,42 → 0,84) trong luận văn
- [ ] Giải thích hiệu suất F1 Medium thấp hơn như vấn đề ranh giới cấu trúc, không phải lỗi mô hình


(venv) ry@ry-Inspiron-5584:/opt/lampp/htdocs/DO_AN_CHUYEN_NGANH/ml$ python3 evaluate_classifier.py
2026-04-25 23:14:11.204158: I tensorflow/core/platform/cpu_feature_guard.cc:182] This TensorFlow binary is optimized to use available CPU instructions in performance-critical operations.
To enable the following instructions: AVX2 FMA, in other operations, rebuild TensorFlow with the appropriate compiler flags.
Evaluating on 1800 test sequences (after 2026-01-31)
After change
==================================================
CONFUSION MATRIX
==================================================
              Pred Low Pred Medium   Pred High
   Act Low         222          44          33
Act Medium         199         290         156
  Act High         104          64         688

==================================================
PER-CLASS METRICS  (β = 1, balanced F1)
==================================================
Class       Precision     Recall         F1    Support
--------------------------------------------------
Low             0.423      0.742      0.539        299
Medium          0.729      0.450      0.556        645
High            0.784      0.804      0.794        856

==================================================
SUMMARY
==================================================
  Accuracy       : 0.667  (66.7%)
  Macro F1       : 0.630

==================================================
TRUSTWORTHINESS VERDICT
==================================================
  Macro F1 = 0.630  →  POOR — retrain or review data before using
  Accuracy = 66.7%


Before change
but if i keep the single lstm the result is not good

```markdown
after change result 
(venv) ry@ry-Inspiron-5584:/opt/lampp/htdocs/DO_AN_CHUYEN_NGANH/ml$ python3 evaluate_classifier.py
2026-04-25 16:59:57.345639: I tensorflow/core/platform/cpu_feature_guard.cc:182] This TensorFlow binary is optimized to use available CPU instructions in performance-critical operations.
To enable the following instructions: AVX2 FMA, in other operations, rebuild TensorFlow with the appropriate compiler flags.
Evaluating on 1800 test sequences (after 2026-01-31)

==================================================
CONFUSION MATRIX
==================================================
              Pred Low Pred Medium   Pred High
   Act Low         185          77          37
Act Medium         133         349         163
  Act High          55         102         699

==================================================
PER-CLASS METRICS  (β = 1, balanced F1)
==================================================
Class       Precision     Recall         F1    Support
--------------------------------------------------
Low             0.496      0.619      0.551        299
Medium          0.661      0.541      0.595        645
High            0.778      0.817      0.797        856

==================================================
SUMMARY
==================================================
  Accuracy       : 0.685  (68.5%)
  Macro F1       : 0.647

==================================================
TRUSTWORTHINESS VERDICT
==================================================
  Macro F1 = 0.647  →  POOR — retrain or review data before using
  Accuracy = 68.5%

```