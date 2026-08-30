# Tài liệu hướng dẫn học phí học sinh và lương giáo viên

## 1. Mục đích và phạm vi

Tài liệu này hướng dẫn cách vận hành hai phân hệ trong menu **Tài chính**:

1. **Học phí học sinh**: cấu hình khoản thu, mức thu, miễn giảm, phát sinh công nợ, ghi nhận thanh toán và xem báo cáo.
2. **Lương giáo viên**: cấu hình thành phần lương, hồ sơ lương, dữ liệu theo tháng, dạy thêm, thuế, bảo hiểm, tạo/duyệt/chốt bảng lương và thanh toán.

Tài liệu được viết theo đúng hành vi của phiên bản hiện tại trong mã nguồn. Tên nút và tên màn hình được giữ giống giao diện để người dùng có thể làm theo trực tiếp.

> **Lưu ý quan trọng:** số liệu thuế, bảo hiểm, học phí và chính sách miễn giảm do nhà trường tự cấu hình. Các ví dụ trong tài liệu chỉ minh họa cách hệ thống tính toán, không phải mức thu hoặc quy định pháp lý.

---

## 2. Đối tượng sử dụng và cách truy cập

### 2.1. Người quản trị hoặc nhân viên tài chính

Sau khi đăng nhập:

1. Mở thanh menu bên trái.
2. Chọn nhóm **Tài chính**.
3. Chọn chức năng cần dùng, ví dụ **Công nợ học sinh** hoặc **Bảng lương**.

Đường dẫn tổng quan của phân hệ là:

```text
/admin/finance
```

Các mục chỉ xuất hiện khi tài khoản có quyền tương ứng. Nếu không thấy menu hoặc không thấy nút **Thêm**, **Phát sinh học phí**, **Tạo bảng lương**, **Duyệt**, **Chốt** hay **Thanh toán**, cần kiểm tra quyền của tài khoản.

### 2.2. Học sinh

Học sinh có quyền `student-fees.view` có thể mở:

```text
/student/payments
```

Màn hình này cho phép xem:

- tổng phải thu, đã thu và còn nợ của dữ liệu mới;
- từng khoản thu, số tiền miễn giảm và trạng thái;
- lịch sử thanh toán mới;
- khoản thu và giao dịch cũ được gắn nhãn **Legacy**.

Phiên bản hiện tại **chỉ cho học sinh tra cứu**, chưa có biểu mẫu để học sinh tự thanh toán trực tuyến.

### 2.3. Giáo viên

Phiên bản hiện tại chưa có màn hình riêng để giáo viên tự xem phiếu lương. Bảng lương được xem và xử lý trong khu vực quản trị **Tài chính** bởi người có quyền.

---

## 3. Các nguyên tắc dữ liệu chung

### 3.1. Dữ liệu được chụp tại thời điểm phát sinh

Khi phát sinh học phí hoặc tính lương, hệ thống lưu một bản chụp (snapshot) gồm tên, mã, số tiền, chính sách hoặc cấu hình đã dùng. Vì vậy:

- sửa tên học sinh, giáo viên, khoản thu hoặc chính sách sau đó không tự sửa lịch sử;
- thay đổi mức thu không tự tính lại khoản phải thu đã phát sinh;
- thay đổi hồ sơ lương không tự sửa bảng lương đã tính;
- lịch sử tài chính vẫn phản ánh dữ liệu tại thời điểm nghiệp vụ được tạo.

### 3.2. Không xóa lịch sử tài chính để sửa sai

Hệ thống ưu tiên giữ dấu vết:

- khoản phải thu sai được **hủy** nếu chưa có thanh toán hợp lệ;
- giao dịch thu/chi sai được hủy hoặc đảo giao dịch theo workflow;
- bảng lương đã chốt được sửa bằng **phiếu điều chỉnh riêng**, không sửa trực tiếp.

### 3.3. Dữ liệu mới và dữ liệu legacy tách biệt

Học phí cũ (legacy) và học phí mới dựa trên `fee assessment` được hiển thị riêng:

- bảng **Công nợ học sinh**, tổng quan và báo cáo học phí mới chỉ tính các khoản phải thu mới;
- giao dịch legacy không được cộng vào tổng mới;
- không dùng workflow mới để hủy giao dịch legacy;
- khi đối chiếu số liệu, phải xem đúng khu vực và không cộng trùng hai nguồn.

---

# PHẦN I - HỌC PHÍ HỌC SINH

## 4. Học phí hoạt động như thế nào?

Luồng chuẩn:

```text
Khoản thu
   -> Mức thu theo năm học và thời gian
   -> Chính sách miễn giảm (nếu có)
   -> Gán diện miễn giảm cho học sinh (nếu có)
   -> Phát sinh học phí
   -> Khoản phải thu của từng học sinh
   -> Thu một phần hoặc thu đủ
   -> Theo dõi công nợ và báo cáo
```

Các đối tượng chính:

| Đối tượng | Ý nghĩa |
|---|---|
| Khoản thu | Loại tiền cần thu, ví dụ học phí, bán trú, đồng phục |
| Mức thu | Số tiền của một khoản thu trong một năm học, chu kỳ và thời gian hiệu lực |
| Chính sách miễn giảm | Quy tắc giảm theo phần trăm, số tiền cố định hoặc miễn toàn bộ |
| Diện miễn giảm | Việc gán một chính sách cụ thể cho một học sinh |
| Đợt phát sinh | Lệnh tạo khoản phải thu cho toàn trường, một lớp hoặc một nhóm học sinh |
| Khoản phải thu | Công nợ đã chụp riêng cho một học sinh |
| Thanh toán | Một lần thu tiền cho khoản phải thu; có thể thu nhiều lần |

## 5. Điều kiện cần trước khi phát sinh học phí

Phải có đủ các dữ liệu sau:

1. Năm học đã được tạo.
2. Lớp thuộc đúng năm học.
3. Học sinh nên có trạng thái **đang học**; danh sách chọn riêng trên giao diện chỉ nạp học sinh ở trạng thái này.
4. Điều kiện quyết định khi Generate là học sinh có bản ghi xếp lớp/enrollment **active** trong năm học đó.
5. Khoản thu đang sử dụng.
6. Mức thu đang dùng, thuộc đúng khoản thu và năm học.
7. Ngày tính nằm trong thời gian hiệu lực của mức thu.

Nếu phát sinh theo lớp hoặc học sinh được chọn nhưng các em chưa có enrollment active trong năm học, hệ thống sẽ không tạo công nợ cho các em đó.

## 6. Cấu hình khoản thu

Vào **Tài chính -> Khoản thu** và chọn **Thêm danh mục**.

### 6.1. Ý nghĩa các trường

| Trường | Cách nhập |
|---|---|
| Mã | Mã duy nhất, chỉ dùng chữ in hoa, số, `_` hoặc `-`, ví dụ `TUITION` |
| Tên | Tên hiển thị, ví dụ `Học phí` |
| Loại | Nhóm nghiệp vụ, ví dụ `tuition`, `service`, `product`, `insurance`, `other` |
| Mô tả | Ghi chú thêm, không bắt buộc |
| Đang sử dụng | Phải bật nếu muốn dùng khoản thu khi cấu hình/phát sinh |

Các danh mục mẫu thường có sẵn sau khi seed dữ liệu: học phí, học thêm, bán trú, đồng phục, tài liệu, bảo hiểm và phí khác.

### 6.2. Khuyến nghị đặt mã

- Dùng mã ổn định và dễ hiểu, ví dụ `TUITION`, `BOARDING`, `UNIFORM`.
- Không dùng mã theo từng tháng; tháng được quản lý ở **Mức thu** và **Khóa kỳ**.
- Không đổi ý nghĩa một mã đã dùng trong lịch sử.

## 7. Cấu hình mức thu

Vào **Tài chính -> Mức thu** và chọn **Thêm mức thu**.

### 7.1. Ý nghĩa các trường

| Trường | Cách dùng |
|---|---|
| Danh mục | Chọn khoản thu đã tạo |
| Năm học | Năm học áp dụng mức tiền này |
| Chu kỳ | `monthly`, `semester`, `annual` hoặc `one_time` |
| Mã | Mã duy nhất, ví dụ `TUITION_2026_MONTHLY` |
| Tên | Ví dụ `Học phí chuẩn theo tháng 2026-2027` |
| Số tiền | Mức gốc trước miễn giảm |
| Từ ngày/Đến ngày | Khoảng ngày mà mức thu có hiệu lực |
| Đang dùng | Phải bật để xuất hiện trong màn hình phát sinh |

Ý nghĩa chu kỳ:

| Giá trị | Ý nghĩa |
|---|---|
| `monthly` | Thu theo tháng |
| `semester` | Thu theo học kỳ |
| `annual` | Thu theo năm học |
| `one_time` | Thu một lần |

### 7.2. Quy tắc chọn mức thu khi phát sinh

Hệ thống kiểm tra đồng thời:

- mức thu đang active;
- khoản thu của mức thu trùng với khoản thu được phát sinh;
- năm học trùng nhau;
- chu kỳ trùng nhau;
- **Ngày tính** nằm từ ngày bắt đầu đến ngày kết thúc hiệu lực.

Nếu một trong các điều kiện không đúng, hệ thống báo: **Mức thu không phù hợp với kỳ phát sinh**.

## 8. Cấu hình miễn giảm

Đây là bước tùy chọn. Nếu không có học sinh miễn giảm, có thể bỏ qua mục 8 và 9.

Vào **Tài chính -> Chính sách miễn giảm** và chọn **Thêm chính sách**.

### 8.1. Các loại giảm

| Loại | Cách tính | Giá trị cần nhập |
|---|---|---|
| Theo phần trăm | Giảm một tỷ lệ trên số tiền còn lại | Từ 0 đến 100 |
| Số tiền cố định | Trừ một số tiền cố định nhưng không làm số phải thu âm | Số tiền >= 0 |
| Miễn toàn bộ | Giảm toàn bộ số tiền còn lại | Bắt buộc nhập 100 |

### 8.2. Khoản thu áp dụng

- Chọn một khoản thu: chính sách chỉ áp dụng cho khoản đó.
- Chọn **Tất cả**: chính sách có thể áp dụng cho mọi khoản thu trong năm học, nếu các điều kiện khác phù hợp.

### 8.3. Chế độ áp dụng và mức ưu tiên

Hệ thống có hai chế độ:

#### Chính sách ưu tiên (`exclusive`)

- Nếu học sinh có ít nhất một chính sách exclusive phù hợp, hệ thống chỉ chọn chính sách exclusive có **mức ưu tiên cao nhất**.
- Các chính sách cumulative sẽ không được dùng trong trường hợp này.
- Nếu có hai chính sách exclusive cùng mức ưu tiên cao nhất, hệ thống dừng và báo lỗi vì không thể xác định chính sách thắng.

#### Cho phép cộng dồn (`cumulative`)

- Chỉ được cộng dồn khi không có chính sách exclusive phù hợp.
- Hệ thống sắp xếp từ mức ưu tiên cao xuống thấp.
- Mỗi chính sách được áp dụng trên số tiền còn lại sau chính sách trước.

Ví dụ mức gốc 500.000 đồng, có giảm 10% rồi giảm cố định 50.000 đồng:

```text
Sau giảm 10%:       500.000 - 50.000 = 450.000
Sau giảm cố định:   450.000 - 50.000 = 400.000
Số phải thu:        400.000
```

### 8.4. Ngày hiệu lực quyết định theo ngày nào?

Chính sách được xét theo **Ngày tính** của đợt phát sinh, không phải ngày người dùng bấm nút và cũng không phải hạn đóng.

Để chính sách được áp dụng, đồng thời phải đúng:

- chính sách active;
- chính sách thuộc đúng năm học;
- khoản thu đúng hoặc chính sách áp dụng cho tất cả;
- ngày tính nằm trong hiệu lực của chính sách;
- diện miễn giảm của học sinh active;
- ngày tính cũng nằm trong hiệu lực của diện miễn giảm.

## 9. Gán diện miễn giảm cho học sinh

Vào **Tài chính -> Diện miễn giảm** và chọn **Gán chính sách**.

Nhập:

1. **Học sinh**: người được hưởng chính sách.
2. **Chính sách**: chính sách đã tạo.
3. **Từ ngày/Đến ngày**: thời gian học sinh được hưởng.
4. **Trạng thái**: chọn **Đang áp dụng**.
5. **Mã minh chứng**: số hồ sơ, quyết định hoặc giấy tờ liên quan.
6. **Ghi chú**: thông tin bổ sung.

Nên hoàn tất việc gán chính sách **trước khi phát sinh học phí**. Khoản phải thu đã tạo sẽ không tự tính lại nếu chính sách được thêm hoặc sửa sau đó.

## 10. Phát sinh học phí

Vào **Tài chính -> Công nợ học sinh**, chọn **Phát sinh học phí**.

### 10.1. Cách nhập từng trường

| Trường | Hướng dẫn |
|---|---|
| Năm học | Chọn năm học cần thu |
| Học kỳ | Chọn nếu khoản thu gắn với học kỳ; nếu không thì chọn **Không áp dụng** |
| Mức thu | Chọn mức đang active; hệ thống tự điền khoản thu và loại kỳ |
| Tên đợt thu | Ví dụ `Học phí tháng 09/2026` |
| Loại kỳ | Thường được tự đặt theo mức thu; không nên đổi sang loại khác |
| Khóa kỳ | Mã kỹ thuật của kỳ, ví dụ `2026-09` |
| Lần phát sinh | Thông thường là `1`; tăng lên nếu cố ý thu thêm cùng khoản và cùng kỳ |
| Ngày tính | Ngày dùng để chọn mức thu và chính sách miễn giảm |
| Hạn đóng | Không bắt buộc, nhưng nên nhập để theo dõi quá hạn; phải >= ngày tính |
| Phạm vi | Toàn trường, theo lớp hoặc học sinh được chọn |
| Lớp | Bắt buộc nếu phạm vi là theo lớp |
| Học sinh chọn riêng | Bắt buộc chọn ít nhất một em nếu phạm vi là học sinh được chọn |
| Ghi chú | Nội dung bổ sung |

### 10.2. Quy tắc đặt Khóa kỳ

Khóa kỳ chỉ nhận chữ không dấu, số và các ký tự `:`, `_`, `-`. Không nhập dấu cách, dấu `/` hoặc tiếng Việt có dấu.

Ví dụ hợp lệ:

```text
2026-09
2026-HK1
2026-2027
NHAP_HOC_2026
```

### 10.3. Ý nghĩa Lần phát sinh

Một khoản phải thu được xem là trùng nếu có cùng:

```text
Học sinh + Năm học + Khoản thu + Khóa kỳ + Lần phát sinh
```

Nếu bấm generate lại với cùng bộ khóa, hệ thống bỏ qua dữ liệu đã tồn tại và thông báo số lượng **bỏ qua**. Đây là cơ chế chống tạo công nợ trùng.

Nếu nhà trường thực sự cần thu hai lần cùng một khoản trong cùng kỳ, giữ nguyên Khóa kỳ nhưng dùng:

- lần đầu: `Lần phát sinh = 1`;
- lần bổ sung: `Lần phát sinh = 2`.

Không tăng Lần phát sinh chỉ để xử lý một đợt đã tạo sai. Trường hợp sai nên hủy khoản chưa thanh toán và tạo lại đúng dữ liệu.

### 10.4. Phạm vi phát sinh

| Phạm vi | Đối tượng được tạo công nợ |
|---|---|
| Toàn trường | Tất cả enrollment active trong năm học |
| Theo lớp | Enrollment active của lớp đã chọn trong năm học |
| Học sinh được chọn | Các học sinh đã chọn nhưng vẫn phải có enrollment active trong năm học |

Sau khi bấm **Generate**, hệ thống trả thông báo dạng:

```text
Đã tạo X khoản phải thu; bỏ qua Y khoản đã tồn tại.
```

### 10.5. Công thức của một khoản phải thu

```text
Mức gốc      = số tiền của Mức thu
Miễn giảm    = kết quả áp dụng chính sách hợp lệ
Phải thu     = Mức gốc - Miễn giảm
Đã thu       = tổng giao dịch thanh toán completed
Còn nợ       = Phải thu - Đã thu
```

Nếu được miễn toàn bộ thì Phải thu = 0 và khoản được coi là đã tất toán.

## 11. Xem và lọc công nợ

Vào **Tài chính -> Công nợ học sinh**.

Có thể lọc theo:

- mã hoặc họ tên học sinh;
- năm học;
- tháng;
- trạng thái: chưa đóng, đóng thiếu, đã tất toán, quá hạn hoặc đã hủy.

Ý nghĩa trạng thái:

| Trạng thái | Ý nghĩa |
|---|---|
| Chưa đóng | Chưa có thanh toán hợp lệ và còn nợ |
| Đóng một phần/Đóng thiếu | Đã thu một phần nhưng vẫn còn nợ |
| Đã tất toán | Còn nợ bằng 0 |
| Quá hạn | Hạn đóng đã qua, còn nợ và khoản chưa bị hủy |
| Đã hủy | Khoản phải thu không còn hiệu lực nhưng lịch sử vẫn được giữ |

**Quá hạn** là điều kiện được suy ra từ hạn đóng và số còn nợ khi lọc/tổng hợp, không phải một bước duyệt riêng.

Nhấp vào **tên học sinh** để xem hồ sơ công nợ của em đó. Nhấp biểu tượng **mắt** để mở chi tiết một khoản phải thu.

## 12. Thu học phí

Tại **Công nợ học sinh**, mở biểu tượng **mắt** của khoản cần thu.

Ở khung **Ghi nhận thanh toán**:

1. Nhập **Số tiền** lớn hơn 0 và không vượt quá số còn nợ.
2. Chọn **Ngày thu**.
3. Chọn phương thức:
   - Tiền mặt;
   - Chuyển khoản;
   - Online;
   - Khác.
4. Nhập **Số biên lai** nếu có.
5. Chọn **Thu tiền**.

Hệ thống cho phép thu nhiều lần:

```text
Phải thu:     500.000
Lần 1 thu:    200.000 -> trạng thái Đóng một phần, còn 300.000
Lần 2 thu:    300.000 -> trạng thái Đã tất toán, còn 0
```

Không thể thu vượt số còn nợ. Khi khoản đã tất toán hoặc đã hủy, biểu mẫu thu tiền không còn xuất hiện.

## 13. Hủy khoản phải thu hoặc giao dịch

### 13.1. Hủy khoản phải thu

Ở trang chi tiết, nhập lý do và chọn **Hủy khoản thu**.

Chỉ hủy được khi khoản chưa có giao dịch thanh toán `completed`. Nếu đã có một lần thu hợp lệ, hệ thống từ chối hủy khoản phải thu.

### 13.2. Hủy giao dịch thu tiền

Backend của hệ thống có workflow hủy giao dịch và sẽ tính lại Đã thu, Còn nợ, Trạng thái. Tuy nhiên giao diện chi tiết hiện tại chỉ hiển thị lịch sử, **chưa có nút hủy giao dịch thanh toán**. Khi cần xử lý, người quản trị hệ thống phải bổ sung giao diện hoặc thực hiện qua quy trình kỹ thuật được kiểm soát; không sửa trực tiếp cơ sở dữ liệu.

Giao dịch legacy không được hủy bằng workflow mới.

## 14. Tra cứu và báo cáo học phí

### 14.1. Tổng quan tài chính

Vào **Tài chính -> Tổng quan**, chọn năm học và tháng phí rồi bấm **Áp dụng**.

Các chỉ số:

- Phải thu;
- Đã thu;
- Còn nợ;
- Miễn giảm;
- số học sinh đóng đủ;
- số học sinh đóng thiếu;
- số học sinh chưa đóng;
- số khoản quá hạn.

### 14.2. Báo cáo học phí

Vào **Tài chính -> Báo cáo học phí** để xem tổng theo:

- Khóa kỳ;
- Khoản thu;
- Mức gốc;
- Miễn giảm;
- Phải thu;
- Đã thu;
- Còn nợ.

Báo cáo không tính khoản đã hủy và không bao gồm thanh toán legacy.

### 14.3. Học sinh tự tra cứu

Học sinh vào **Học phí và thanh toán** để xem số liệu của chính mình. Các khoản legacy được gắn cảnh báo và không cộng vào ba ô tổng mới.

## 15. Ví dụ hoàn chỉnh về học phí

Giả sử:

- khoản thu: Học phí;
- mức thu tháng 09/2026: 500.000 đồng;
- học sinh Nguyễn Văn A có chính sách giảm 50%;
- ngày tính: 01/09/2026;
- hạn đóng: 15/09/2026.

Thực hiện:

1. Tạo/kiểm tra khoản thu `TUITION`.
2. Tạo mức thu `TUITION_2026_MONTHLY`, số tiền 500.000, chu kỳ monthly, hiệu lực bao phủ ngày 01/09/2026.
3. Tạo chính sách giảm 50%, active, hiệu lực từ 01/09/2026.
4. Gán chính sách cho Nguyễn Văn A, active từ 01/09/2026.
5. Phát sinh với Khóa kỳ `2026-09`, Lần phát sinh `1`, Ngày tính `01/09/2026`.

Kết quả:

```text
Mức gốc:       500.000
Miễn giảm:     250.000
Phải thu:      250.000
Đã thu:              0
Còn nợ:        250.000
Trạng thái:    Chưa đóng
```

Nếu thu 100.000, trạng thái chuyển thành Đóng một phần và còn nợ 150.000. Khi thu tiếp 150.000, trạng thái chuyển thành Đã tất toán.

## 16. Lỗi học phí thường gặp

| Hiện tượng/thông báo | Nguyên nhân thường gặp | Cách xử lý |
|---|---|---|
| Không thấy Mức thu | Mức thu inactive hoặc chưa được tạo | Bật Đang dùng hoặc tạo mức thu |
| Mức thu không phù hợp với kỳ phát sinh | Sai năm học, khoản thu, chu kỳ hoặc ngày tính ngoài hiệu lực | Đối chiếu lại 4 thông tin này |
| Tạo ra 0 khoản | Không có enrollment active trong phạm vi/năm học | Kiểm tra xếp lớp và trạng thái enrollment |
| Bỏ qua nhiều khoản | Bộ khóa công nợ đã tồn tại | Kiểm tra Khóa kỳ và Lần phát sinh |
| Miễn giảm không được áp dụng | Chưa gán cho học sinh, sai ngày hiệu lực, năm học, khoản thu hoặc trạng thái | Kiểm tra cả Chính sách và Diện miễn giảm theo Ngày tính |
| Lỗi nhiều policy exclusive cùng ưu tiên | Có hai chính sách ưu tiên cao nhất bằng nhau | Đặt mức ưu tiên khác nhau hoặc ngừng một chính sách |
| Không thể hủy khoản phải thu | Đã có thanh toán completed | Xử lý giao dịch thanh toán trước theo workflow |
| Số tiền thanh toán vượt công nợ | Nhập lớn hơn Còn nợ | Chỉ nhập tối đa số còn nợ |
| Tổng mới không khớp lịch sử cũ | Dữ liệu mới và legacy tách riêng | Đối chiếu từng nguồn, không cộng trùng |

## 17. Checklist vận hành học phí hàng tháng

- [ ] Học sinh đã được xếp lớp active đúng năm học.
- [ ] Mức thu đúng khoản, đúng chu kỳ và còn hiệu lực.
- [ ] Chính sách và diện miễn giảm đã được cập nhật trước ngày phát sinh.
- [ ] Khóa kỳ đúng định dạng, ví dụ `2026-09`.
- [ ] Lần phát sinh đúng mục đích, thông thường là `1`.
- [ ] Phạm vi toàn trường/lớp/học sinh được chọn đã đúng.
- [ ] Kiểm tra số lượng tạo và số lượng bỏ qua sau Generate.
- [ ] Đối chiếu vài học sinh có và không có miễn giảm.
- [ ] Ghi nhận thanh toán đúng khoản, đúng số tiền, đúng ngày và phương thức.
- [ ] Kiểm tra công nợ thiếu, chưa đóng và quá hạn trên Tổng quan.

---

# PHẦN II - LƯƠNG GIÁO VIÊN

## 18. Lương giáo viên hoạt động như thế nào?

Luồng cấu hình ban đầu:

```text
Thành phần lương
   -> Cấu hình thuế/bảo hiểm (nếu dùng)
   -> Hồ sơ lương có hiệu lực cho từng giáo viên
```

Luồng hàng tháng:

```text
Đầu vào lương theo tháng + Dạy thêm đã duyệt
   -> Tạo bảng lương
   -> Kiểm tra và Tính lại nếu cần
   -> Duyệt
   -> Chốt bảng lương
   -> Thanh toán một phần hoặc toàn bộ
   -> Phiếu điều chỉnh riêng nếu phát hiện sai sau khi chốt
```

## 19. Các khái niệm lương

| Khái niệm | Ý nghĩa |
|---|---|
| Thành phần lương | Loại thu nhập hoặc khấu trừ, kèm cách tính |
| Hồ sơ lương | Cấu hình các thành phần áp dụng cho một giáo viên trong một khoảng hiệu lực |
| Đầu vào lương | Dữ liệu biến động theo tháng như thưởng, nghỉ không lương, làm thêm |
| Dạy thêm | Số tiết theo môn/lớp/tháng, đơn giá lấy từ hồ sơ lương |
| Payroll/Bảng lương | Bản chụp kết quả tính của một giáo viên trong một tháng |
| Thanh toán lương | Một lần trả tiền trên bảng lương đã chốt |
| Adjustment | Phiếu tăng/giảm riêng sau khi bảng lương đã chốt hoặc đã trả |

## 20. Trạng thái bảng lương

```text
draft -> calculated -> approved -> locked -> paid
  |          |           |          |
  +----------+-----------+----------+-> cancelled (khi chưa có thanh toán)
```

Trên giao diện tiếng Việt:

| Trạng thái | Ý nghĩa | Có thể làm gì tiếp? |
|---|---|---|
| Bản nháp (`draft`) | Bản ghi ban đầu | Tính lại |
| Đã tính (`calculated`) | Đã hình thành các dòng lương | Tính lại hoặc Duyệt |
| Đã duyệt (`approved`) | Đã được người có quyền duyệt | Chốt bảng lương |
| Đã chốt (`locked`) | Snapshot đã khóa | Thanh toán hoặc tạo adjustment |
| Đã thanh toán (`paid`) | Đã trả đủ net salary | Chỉ tạo adjustment riêng khi cần |
| Đã hủy (`cancelled`) | Bảng lương không còn hiệu lực | Chỉ tra cứu lịch sử |

Không thể bỏ qua thứ tự **Đã tính -> Đã duyệt -> Đã chốt -> Đã thanh toán**.

Backend cho phép hủy bảng lương chưa thanh toán (trừ trạng thái paid/cancelled), nhưng trang chi tiết hiện tại chưa có nút hủy bảng lương. Khi cần hủy phải đi qua quy trình kỹ thuật được kiểm soát, không sửa trực tiếp cơ sở dữ liệu.

## 21. Cấu hình thành phần lương

Vào **Tài chính -> Thành phần lương**.

Hệ thống thường có sẵn các thành phần:

| Mã | Tên | Loại | Nhóm | Cách tính |
|---|---|---|---|---|
| `BASE_SALARY` | Lương cơ bản | Thu nhập | `base_salary` | Cố định |
| `ALLOWANCE` | Phụ cấp | Thu nhập | `allowance` | Cố định |
| `EXTRA_TEACHING` | Dạy thêm | Thu nhập | `teaching_extra` | Theo đơn vị |
| `BONUS` | Thưởng | Thu nhập | `bonus` | Nhập thủ công |
| `ADVANCE` | Tạm ứng | Khấu trừ | `advance` | Nhập thủ công |
| `UNPAID_LEAVE` | Nghỉ không lương | Khấu trừ | `unpaid_leave` | Theo đơn vị |
| `OTHER_DEDUCTION` | Khấu trừ khác | Khấu trừ | `other_deduction` | Nhập thủ công |

### 21.1. Ý nghĩa các trường

| Trường | Cách dùng |
|---|---|
| Mã | Dùng chữ in hoa, số, `_`, `:`, `-`; phải duy nhất |
| Tên | Tên hiển thị trên chi tiết bảng lương |
| Loại | Thu nhập (`earning`) hoặc Khấu trừ (`deduction`) |
| Nhóm | Quyết định hệ thống cộng vào lương cơ bản, phụ cấp, dạy thêm, thưởng... |
| Cách tính | Cố định, phần trăm, theo đơn vị hoặc nhập thủ công |
| Chịu thuế | Thành phần thu nhập này được đưa vào thu nhập chịu thuế |
| Làm nền bảo hiểm | Thành phần thu nhập này được dùng khi nền bảo hiểm là `insurance_earnings` |
| Đang sử dụng | Chỉ thành phần active mới được sử dụng |

Nhóm (`category`) là mã kỹ thuật quan trọng. Nên dùng đúng các giá trị hệ thống hiểu:

```text
base_salary
allowance
teaching_extra
bonus
insurance
tax
advance
unpaid_leave
other_deduction
```

### 21.2. Bốn cách tính

| Cách tính | Nguồn số tiền |
|---|---|
| Cố định (`fixed`) | Nhập Fixed trong hồ sơ lương |
| Phần trăm (`percentage`) | Tỷ lệ % nhân với nền tính, mặc định là lương cơ bản trên giao diện hiện tại |
| Theo đơn vị (`per_unit`) | Rate x Quantity |
| Nhập thủ công (`manual`) | Số tiền nhập trong Đầu vào lương theo tháng |

Không nên nhập thành phần `manual` như một khoản cố định trong hồ sơ lương rồi kỳ vọng hệ thống tự cộng. Thành phần manual chỉ phát sinh số tiền khi có **Đầu vào lương** đã duyệt.

## 22. Cấu hình thuế thu nhập

Chỉ cần cấu hình nếu có giáo viên bật **Tính thuế** trong hồ sơ lương.

Vào **Tài chính -> Thuế** và chọn **Thêm cấu hình thuế**.

Nhập:

- mã và tên cấu hình;
- năm thuế;
- giảm trừ cá nhân;
- giảm trừ cho mỗi người phụ thuộc;
- ngày hiệu lực;
- các bậc thuế gồm: thứ tự, cận dưới, cận trên, tỷ lệ và giảm nhanh;
- bật Active.

### 22.1. Công thức thuế

```text
Thu nhập chịu thuế
  = Tổng các khoản earning được đánh dấu Chịu thuế
    - Giảm trừ cá nhân
    - (Giảm trừ phụ thuộc x Số người phụ thuộc)
    - Bảo hiểm người lao động

Thuế
  = max(0, Thu nhập chịu thuế x Tỷ lệ bậc thuế - Giảm nhanh)
```

### 22.2. Quy tắc quan trọng

- Mỗi kỳ lương bật tính thuế phải tìm được **đúng một** cấu hình thuế active, đúng năm thuế và còn hiệu lực tại cuối tháng.
- Các bậc phải bao phủ thu nhập tính thuế mà không tạo khoảng trống hoặc chồng lấn. Thu nhập phải khớp đúng một bậc.
- Nếu không muốn tính thuế cho một giáo viên, bỏ chọn **Tính thuế** trong hồ sơ lương thay vì tạo cấu hình 0 không rõ mục đích.

## 23. Cấu hình bảo hiểm

Chỉ cần cấu hình nếu có giáo viên bật **Tính bảo hiểm**.

Vào **Tài chính -> Bảo hiểm** và chọn **Thêm cấu hình bảo hiểm**.

### 23.1. Các trường chính

| Trường | Ý nghĩa |
|---|---|
| Tỷ lệ NLĐ % | Phần khấu trừ vào lương giáo viên |
| Tỷ lệ đơn vị % | Tỷ lệ của đơn vị; hiện được lưu cấu hình nhưng không khấu trừ vào net salary của giáo viên |
| Nền lương | Cách xác định số tiền làm căn cứ |
| Nền cố định | Dùng khi chọn nền `fixed` |
| Nền tối đa | Giới hạn trần của căn cứ tính |
| Từ ngày/Đến ngày | Khoảng hiệu lực |
| Active | Bật để được dùng |

Các loại nền:

| Giá trị | Căn cứ tính |
|---|---|
| `base_salary` | Tổng thành phần earning nhóm lương cơ bản |
| `gross` | Tổng thu nhập trước khấu trừ |
| `insurance_earnings` | Tổng earning được đánh dấu làm nền bảo hiểm |
| `fixed` | Số tiền nhập ở Nền cố định |

Công thức:

```text
Nền thực tế = min(Nền đã chọn, Nền tối đa nếu có)
Khấu trừ BH của giáo viên = Nền thực tế x Tỷ lệ NLĐ
```

Hệ thống cộng tất cả cấu hình bảo hiểm active và cùng hiệu lực. Có thể dùng nhiều cấu hình riêng cho từng loại bảo hiểm, nhưng phải tránh cấu hình trùng gây khấu trừ hai lần.

## 24. Tạo hồ sơ lương cho giáo viên

Vào **Tài chính -> Cấu hình lương**, chọn **Tạo hồ sơ**.

### 24.1. Thông tin chung

| Trường | Hướng dẫn |
|---|---|
| Giáo viên | Chọn đúng giáo viên |
| Mã profile | Mã duy nhất, ví dụ `SALARY-GV000123-2026` |
| Từ ngày/Đến ngày | Thời gian hiệu lực |
| Người phụ thuộc | Dùng để tính giảm trừ thuế |
| Trạng thái | Chọn `active` để dùng khi tính lương |
| Tính thuế | Bật nếu áp dụng thuế |
| Tính bảo hiểm | Bật nếu áp dụng bảo hiểm |

Để tránh khác biệt giữa ngày ghi nhận dạy thêm và ngày tính bảng lương, nên để hồ sơ lương bao phủ **toàn bộ tháng lương**.

Mỗi giáo viên chỉ được có một hồ sơ `active` tại một thời điểm. Hệ thống không cho lưu hai hồ sơ active có thời gian chồng nhau.

### 24.2. Thêm các dòng Components

Chọn **Thêm dòng** và nhập theo cách tính của component:

| Cột giao diện | Nhập khi nào? |
|---|---|
| Component | Luôn chọn |
| Global/Môn | Chọn Global cho cấu hình chung; chọn môn cho đơn giá riêng theo môn |
| Fixed | Nhập cho component cố định |
| % | Nhập cho component phần trăm |
| Rate | Nhập cho component theo đơn vị |
| Qty | Số lượng mặc định nếu muốn hệ thống tự sinh khoản theo đơn vị mỗi tháng |

Ví dụ hồ sơ:

| Component | Phạm vi | Fixed | Rate | Qty |
|---|---|---:|---:|---:|
| Lương cơ bản | Global | 8.000.000 | | |
| Phụ cấp | Global | 1.000.000 | | |
| Dạy thêm | Toán | | 100.000 | |
| Nghỉ không lương | Global | | 100.000 | |

Với dạy thêm, có thể cấu hình:

- đơn giá riêng cho từng môn; hoặc
- một dòng Global làm đơn giá dự phòng.

Khi ghi nhận dạy thêm, hệ thống ưu tiên đơn giá theo môn, nếu không có mới dùng Global.

### 24.3. Thay đổi hồ sơ lương

- Hồ sơ chưa được dùng trong payroll có thể sửa.
- Hồ sơ đã được dùng trong bất kỳ payroll nào không được sửa cấu hình trực tiếp.
- Khi thay đổi lương, hãy kết thúc hiệu lực hồ sơ cũ và tạo một hồ sơ mới với ngày bắt đầu phù hợp.

Cách này bảo vệ lịch sử và giúp biết chính xác mức lương nào có hiệu lực ở mỗi kỳ.

## 25. Nhập dữ liệu lương theo tháng

Vào **Tài chính -> Đầu vào lương**, chọn **Thêm dữ liệu**.

### 25.1. Chọn loại dữ liệu

| Loại input | Cách nhập |
|---|---|
| Ngày công (`working_days`) | Nhập Quantity; hệ thống tính theo cấu hình component |
| Nghỉ không lương (`unpaid_leave`) | Nhập Quantity; thường chọn component Nghỉ không lương |
| Làm thêm giờ (`overtime`) | Nhập Quantity; cần component và Rate phù hợp |
| Thưởng (`bonus`) | Nhập Input amount; thường chọn component Thưởng |
| Điều chỉnh thủ công (`manual_adjustment`) | Nhập Input amount và chọn component thu nhập/khấu trừ phù hợp |
| Khoản nhập khác (`other_input`) | Nhập Input amount |

Đối với `bonus`, `manual_adjustment`, `other_input`, hệ thống bắt buộc **Input amount**.

Đối với các loại còn lại, hệ thống bắt buộc **Quantity** và cần tìm được cấu hình của component trong hồ sơ lương để tính số tiền.

### 25.2. Trạng thái đầu vào

- `draft`: bản nháp, còn sửa được.
- `approved`: đã duyệt, sẽ được đưa vào lần tính lương phù hợp.

Giao diện hiện tại không có nút duyệt riêng cho đầu vào lương chung. Khi tạo hoặc sửa bản nháp, chọn trực tiếp trạng thái `approved` rồi lưu nếu dữ liệu đã được kiểm tra.

Đầu vào đã approved hoặc đã được đưa vào payroll không thể sửa trực tiếp.

### 25.3. Chọn component đúng bản chất

Hệ thống dùng **Loại của component** để quyết định cộng hay trừ:

- component `earning`: cộng vào Gross;
- component `deduction`: cộng vào Khấu trừ.

Do đó, một input `manual_adjustment` chọn component Thưởng sẽ làm tăng thu nhập, còn chọn Khấu trừ khác sẽ làm giảm Net. Luôn kiểm tra component trước khi lưu.

## 26. Ghi nhận dạy thêm

Vào **Tài chính -> Dạy thêm**, chọn **Ghi nhận tiết dạy**.

Nhập:

1. Giáo viên.
2. Năm học.
3. Học kỳ.
4. Môn.
5. Lớp.
6. Năm và tháng tính lương.
7. Số tiết.
8. Ghi chú.

### 26.1. Hệ thống tự kiểm tra và tính

Hệ thống yêu cầu:

- giáo viên active;
- năm học, học kỳ và lớp khớp nhau;
- môn active;
- giáo viên có phân công giảng dạy active đúng lớp, môn và học kỳ;
- có hồ sơ lương active tại ngày đầu của tháng ghi nhận;
- hồ sơ có component nhóm `teaching_extra`, cách tính `per_unit` và có Rate;
- số tiết lớn hơn 0.

Người dùng **không nhập đơn giá và thành tiền**. Máy chủ lấy Rate từ hồ sơ lương và tính:

```text
Tiền dạy thêm = Số tiết x Đơn giá mỗi tiết
```

### 26.2. Bản ghi trùng trong cùng tháng

Với cùng giáo viên, phân công, năm học, học kỳ và tháng, hệ thống dùng một bản ghi. Nếu bản ghi còn `draft`, nhập lại sẽ cập nhật bản ghi đó thay vì cộng thêm một dòng mới.

Vì vậy nên nhập **tổng số tiết của phân công trong tháng**. Khi dữ liệu đúng, tại danh sách Dạy thêm chọn **Duyệt**. Bản ghi đã duyệt không thể cập nhật trực tiếp và mới được đưa vào bảng lương.

## 27. Tạo bảng lương tháng

Trước khi tạo, hoàn tất:

- hồ sơ lương active cho từng giáo viên;
- đầu vào lương tháng đã approved;
- dạy thêm tháng đã approved;
- cấu hình thuế/bảo hiểm nếu hồ sơ bật các tùy chọn này.

Vào **Tài chính -> Bảng lương**, chọn **Tạo bảng lương**.

Nhập:

1. **Năm** và **Tháng** lương.
2. **Năm học** nếu muốn lưu kèm; trường này có thể để Không chọn.
3. Chọn một hoặc nhiều giáo viên.
4. Nếu không chọn giáo viên, hệ thống tạo cho toàn bộ giáo viên active.
5. Chọn **Generate**.

Hệ thống tạo tối đa một bảng lương cho mỗi giáo viên trong mỗi tháng. Generate lại cùng giáo viên/tháng sẽ được tính là **bỏ qua**, không tạo bản thứ hai.

Thông báo kết quả:

```text
Đã tạo X payroll; bỏ qua Y; lỗi Z.
```

Nếu có lỗi, kiểm tra các nguyên nhân ở mục 34.

## 28. Hệ thống tính lương như thế nào?

### 28.1. Chọn hồ sơ lương

Tại **ngày cuối tháng lương**, hệ thống phải tìm được đúng một hồ sơ lương:

- thuộc giáo viên;
- trạng thái active;
- còn hiệu lực.

Không có hồ sơ hoặc có nhiều hồ sơ cùng hiệu lực đều làm việc tính lương thất bại.

### 28.2. Hình thành các dòng lương

Hệ thống lần lượt lấy:

1. Component cố định trong hồ sơ lương.
2. Component theo đơn vị có Quantity mặc định.
3. Component phần trăm.
4. Đầu vào lương approved đúng giáo viên/tháng và chưa thuộc payroll khác.
5. Dạy thêm approved đúng giáo viên/tháng và chưa thuộc payroll khác.
6. Khấu trừ bảo hiểm nếu bật.
7. Khấu trừ thuế nếu bật.

Component nhóm `teaching_extra`, `insurance`, `tax` không được cộng trực tiếp như component cố định trong hồ sơ:

- dạy thêm đến từ bản ghi Dạy thêm đã duyệt;
- bảo hiểm và thuế do hệ thống sinh từ cấu hình tương ứng.

### 28.3. Công thức tổng

```text
Gross = Tổng mọi dòng có loại Thu nhập

Tổng khấu trừ
  = Các component loại Khấu trừ
    + Bảo hiểm người lao động
    + Thuế thu nhập

Net = Gross - Tổng khấu trừ
```

Nếu tổng khấu trừ lớn hơn Gross, hệ thống không cho hoàn tất phép tính.

Các chỉ tiêu hiển thị riêng:

```text
Lương cứng  = tổng earning nhóm base_salary
Phụ cấp     = tổng earning nhóm allowance
Dạy thêm    = tổng earning nhóm teaching_extra
Thưởng      = tổng earning nhóm bonus
```

### 28.4. Thành phần phần trăm

Trên giao diện hồ sơ hiện tại, component phần trăm mặc định tính trên **lương cơ bản**. Backend cũng hỗ trợ nền Gross nhưng biểu mẫu hiện chưa có ô chọn nền này.

## 29. Kiểm tra và tính lại bảng lương

Sau Generate, vào danh sách **Bảng lương**, tìm theo giáo viên/năm/tháng rồi chọn biểu tượng **mắt**.

Kiểm tra:

- Gross;
- Tổng khấu trừ;
- Net;
- Đã trả và Còn trả;
- từng dòng thành phần;
- số lượng, đơn giá hoặc tỷ lệ;
- môn/lớp của dạy thêm.

Nếu bảng lương đang là **Bản nháp** hoặc **Đã tính**, có thể sửa dữ liệu nguồn phù hợp rồi chọn **Tính lại**.

Tính lại sẽ tạo lại các dòng từ dữ liệu nguồn. Không thể tính lại khi bảng lương đã Duyệt, Chốt, Thanh toán hoặc Hủy.

## 30. Duyệt và chốt bảng lương

Tại chi tiết bảng lương:

1. Khi trạng thái là **Đã tính**, chọn **Duyệt**.
2. Khi trạng thái là **Đã duyệt**, chọn **Chốt bảng lương**.
3. Sau khi chốt, trạng thái là **Đã chốt** và snapshot không thể sửa trực tiếp.

Nên phân công người lập và người duyệt khác nhau thông qua quyền tài khoản, dù hệ thống hiện không bắt buộc hai người phải khác nhau.

## 31. Thanh toán lương

Chỉ bảng lương **Đã chốt** mới có biểu mẫu Thanh toán.

Thực hiện:

1. Mở chi tiết bảng lương.
2. Nhập số tiền; mặc định là toàn bộ số còn trả.
3. Chọn ngày thanh toán.
4. Chọn chuyển khoản, tiền mặt hoặc phương thức khác.
5. Chọn **Ghi nhận**.

Có thể trả nhiều lần, miễn mỗi lần:

- số tiền > 0;
- không vượt số còn trả;
- bảng lương vẫn ở trạng thái locked.

Nếu trả một phần, bảng lương vẫn **Đã chốt** và Còn trả giảm xuống. Khi tổng thanh toán bằng Net, trạng thái chuyển thành **Đã thanh toán**.

Backend hỗ trợ hủy giao dịch trả lương và tính lại số đã trả, nhưng giao diện hiện tại **chưa hiển thị nút hủy giao dịch lương**. Không sửa trực tiếp dữ liệu trong cơ sở dữ liệu.

## 32. Điều chỉnh sau khi chốt

Nếu phát hiện thiếu hoặc thừa sau khi bảng lương đã Chốt/Đã thanh toán, không mở khóa hoặc sửa Net cũ.

Tại chi tiết bảng lương, dùng khung **Adjustment**:

1. Chọn component.
2. Nhập số tiền > 0.
3. Nhập lý do.
4. Chọn **Tạo adjustment riêng**.

Loại của component quyết định bản chất:

- `earning`: khoản bổ sung cho giáo viên;
- `deduction`: khoản cần khấu trừ/thu hồi.

Workflow adjustment:

```text
draft -> approved -> locked -> paid
```

Trên giao diện:

1. **Duyệt** phiếu nháp.
2. **Khóa** phiếu đã duyệt.
3. **Quyết toán** phiếu đã khóa.

Phiếu adjustment là chứng từ riêng:

- không thay đổi Net của bảng lương gốc;
- không thay đổi Đã trả/Còn trả của bảng lương gốc;
- dashboard và báo cáo lương hiện tại tổng hợp bảng `payrolls`, chưa cộng adjustment vào chỉ tiêu Gross/Net.

Khi đối chiếu thực chi, cần xem cả bảng lương gốc và lịch sử adjustment.

## 33. Ví dụ tính lương hoàn chỉnh

Giả sử một giáo viên có:

```text
Lương cơ bản:             8.000.000
Phụ cấp:                  1.000.000
Dạy thêm:                20 x 100.000 = 2.000.000
Thưởng:                     500.000
Khấu trừ khác:            1.000.000
```

Gross:

```text
8.000.000 + 1.000.000 + 2.000.000 + 500.000 = 11.500.000
```

Giả sử bảo hiểm NLĐ là 10% lương cơ bản:

```text
Bảo hiểm = 8.000.000 x 10% = 800.000
```

Giả sử không có giảm trừ cá nhân/phụ thuộc và thuế là 10% trên thu nhập chịu thuế:

```text
Thu nhập chịu thuế = 11.500.000 - 800.000 = 10.700.000
Thuế = 10.700.000 x 10% = 1.070.000
```

Tổng khấu trừ và Net:

```text
Tổng khấu trừ = 1.000.000 + 800.000 + 1.070.000 = 2.870.000
Net = 11.500.000 - 2.870.000 = 8.630.000
```

Các con số này chỉ minh họa thuật toán.

## 34. Lỗi lương thường gặp

| Hiện tượng/thông báo | Nguyên nhân thường gặp | Cách xử lý |
|---|---|---|
| Giáo viên chưa có cấu hình lương hiệu lực | Không có profile active bao phủ kỳ | Tạo/active hồ sơ và kiểm tra ngày hiệu lực |
| Có nhiều cấu hình lương cùng hiệu lực | Hai profile active bị chồng ngày | Kết thúc/ngừng một profile |
| Component yêu cầu Fixed/%/Rate | Thiếu giá trị theo cách tính | Điền đúng cột trong hồ sơ lương |
| Input thiếu cấu hình để server tính | Input theo Quantity nhưng hồ sơ không có component/Rate | Bổ sung cấu hình rồi tính lại |
| Input thủ công yêu cầu số tiền | Bonus/manual/other chưa nhập Input amount | Nhập số tiền |
| Input theo đơn vị yêu cầu số lượng | working_days/unpaid_leave/overtime chưa nhập Quantity | Nhập số lượng |
| Giáo viên không có phân công phù hợp | Dạy thêm sai lớp/môn/học kỳ hoặc chưa phân công | Kiểm tra phân công giảng dạy active |
| Chưa có đơn giá dạy thêm | Hồ sơ thiếu component teaching_extra per_unit cho môn/Global | Thêm Rate phù hợp |
| Thiếu component dạy thêm đang hoạt động | Có record dạy thêm nhưng master component bị inactive/xóa | Bật hoặc tạo component đúng nhóm |
| Payroll yêu cầu đúng một cấu hình thuế | Không có hoặc có nhiều tax config cùng hiệu lực | Đảm bảo đúng một cấu hình cho năm/kỳ |
| Không có đúng một bậc thuế | Bậc thuế bị hở hoặc chồng | Sửa cận dưới/cận trên |
| Tổng khấu trừ vượt gross salary | Input/component khấu trừ quá lớn | Kiểm tra số tiền và component |
| Generate báo bỏ qua | Payroll giáo viên/tháng đã tồn tại | Mở payroll hiện có; không tạo lần hai |
| Không thấy nút Tính lại | Payroll đã duyệt/chốt/trả/hủy | Nếu đã chốt, dùng adjustment |
| Chỉ payroll đã khóa mới được thanh toán | Chưa hoàn tất Duyệt và Chốt | Thực hiện đúng chuỗi trạng thái |
| Số tiền vượt quá lương còn phải trả | Nhập lớn hơn Remaining | Chỉ trả tối đa số còn trả |

## 35. Tổng quan và báo cáo lương

### 35.1. Tổng quan

Vào **Tài chính -> Tổng quan**, nhập Tháng lương và Năm lương, sau đó chọn **Áp dụng**.

Hệ thống hiển thị:

- Gross;
- Khấu trừ;
- Thuế;
- Bảo hiểm;
- Dạy thêm;
- Phụ cấp;
- Net/Thực nhận;
- số giáo viên đã nhận/chưa nhận.

### 35.2. Báo cáo lương

Vào **Tài chính -> Báo cáo lương**.

Báo cáo nhóm theo:

- tháng/năm;
- bộ môn/chuyên môn của giáo viên tại thời điểm tính;
- Gross, phụ cấp, dạy thêm, thuế, bảo hiểm, khấu trừ và Net.

Bảng lương đã hủy không được tính. Như đã lưu ý, adjustment sau chốt chưa được cộng vào dashboard hoặc báo cáo tổng này.

## 36. Checklist vận hành lương hàng tháng

- [ ] Mỗi giáo viên có đúng một hồ sơ lương active bao phủ cả tháng.
- [ ] Các component cần dùng đang active và đúng loại earning/deduction.
- [ ] Lương cố định, phần trăm, đơn giá và số người phụ thuộc đã đúng.
- [ ] Cấu hình thuế đúng năm, đúng hiệu lực và các bậc không hở/chồng.
- [ ] Cấu hình bảo hiểm đúng nền, tỷ lệ, hiệu lực và không bị trùng.
- [ ] Các input thưởng, nghỉ, tạm ứng, khấu trừ... đúng tháng và đã approved.
- [ ] Dạy thêm đúng phân công, đúng tháng, đúng số tiết và đã approved.
- [ ] Generate cho đúng giáo viên/tháng và kiểm tra số lỗi/bỏ qua.
- [ ] Mở chi tiết để đối chiếu từng dòng trước khi Duyệt.
- [ ] Chỉ Chốt sau khi người có trách nhiệm đã kiểm tra.
- [ ] Thanh toán không vượt số Còn trả và đúng phương thức/ngày.
- [ ] Sai sót sau chốt được xử lý bằng adjustment riêng.
- [ ] Khi đối chiếu thực chi, cộng/đối chiếu cả adjustment ngoài bảng lương gốc.

---

## 37. Phân quyền cần lưu ý

Menu và nút thao tác phụ thuộc quyền. Các nhóm quyền chính:

| Nghiệp vụ | Quyền tiêu biểu |
|---|---|
| Xem tổng quan | `finance.dashboard` |
| Cấu hình khoản thu | `fee-categories.view/create/edit/delete` |
| Cấu hình mức thu | `fee-rates.view/create/edit/delete` |
| Cấu hình miễn giảm | `fee-policies.view/create/edit/delete` |
| Gán diện miễn giảm | `student-fee-policies.view/create/edit/delete` |
| Xem/phát sinh/thu/hủy học phí | `fees.view`, `fees.generate`, `fees.pay`, `fees.cancel` |
| Báo cáo học phí | `fees.report` |
| Cấu hình thành phần lương | `salary-components.view/create/edit/delete` |
| Hồ sơ lương | `salary-configs.view/create/edit/delete` |
| Đầu vào lương | `payroll-inputs.view/create/edit` |
| Dạy thêm | `teaching-extra.view/create`, `teaching-extra.approve` |
| Thuế và bảo hiểm | `tax-configs.*`, `insurance-configs.*` |
| Xem/tính/duyệt/chốt/trả lương | `payrolls.view/calculate/approve/lock/pay` |
| Hủy và điều chỉnh lương | `payrolls.cancel`, `payrolls.adjust` |
| Báo cáo lương | `payrolls.report` |
| Học sinh tự xem học phí | `student-fees.view` |

Nếu người dùng xem được danh sách nhưng không thấy nút hành động, thường là tài khoản chỉ có quyền `view` mà chưa có quyền hành động tương ứng.

## 38. Quy trình phân công trách nhiệm đề xuất

Để giảm sai sót, có thể phân vai:

| Vai trò | Công việc |
|---|---|
| Quản trị hệ thống | Quản lý quyền, dữ liệu nền, hỗ trợ hủy giao dịch chưa có UI |
| Kế toán học phí | Cấu hình mức thu, phát sinh, thu tiền, đối chiếu công nợ |
| Người phụ trách chính sách | Duyệt hồ sơ và gán diện miễn giảm |
| Người nhập lương | Cập nhật profile, input tháng và dạy thêm |
| Người duyệt lương | Kiểm tra chi tiết và bấm Duyệt |
| Người chốt/chi lương | Chốt bảng lương, ghi nhận thanh toán, quyết toán adjustment |

Tối thiểu nên tách quyền **tạo/tính** khỏi quyền **duyệt/chốt** trong môi trường vận hành thật.

## 39. Ghi chú về giao diện hiện tại

1. Một số nhãn kỹ thuật vẫn dùng tiếng Anh như `Generate`, `Gross`, `Net`, `Component`, `Fixed`, `Rate`, `Qty`, `Adjustment`.
2. Học sinh chỉ xem học phí, chưa tự thực hiện thanh toán.
3. Chưa có cổng riêng để giáo viên xem phiếu lương.
4. Chưa có nút hủy bảng lương hoặc hủy từng giao dịch thu học phí/trả lương trên màn hình chi tiết, dù backend đã có workflow.
5. Form adjustment hiện quyết toán bằng phương thức chuyển khoản khi bấm nút trên giao diện.
6. Dashboard và báo cáo lương chưa cộng các adjustment sau chốt.
7. Dữ liệu legacy được giữ riêng và không cộng vào tổng học phí mới.

Các điểm này cần được tính đến khi viết quy trình nội bộ hoặc khi mở rộng phần mềm.

## 40. Tóm tắt nhanh

### Học phí

```text
Khoản thu -> Mức thu -> Miễn giảm -> Gán học sinh
-> Phát sinh -> Kiểm tra công nợ -> Thu tiền -> Báo cáo
```

### Lương

```text
Thành phần -> Thuế/Bảo hiểm -> Hồ sơ lương
-> Input tháng + Dạy thêm đã duyệt
-> Generate -> Kiểm tra/Tính lại -> Duyệt -> Chốt -> Thanh toán
-> Adjustment riêng nếu sai sau chốt
```

Nguyên tắc quan trọng nhất: **cấu hình và duyệt dữ liệu nguồn trước khi phát sinh; không sửa trực tiếp dữ liệu tài chính đã khóa; luôn dùng workflow hủy hoặc điều chỉnh để giữ lịch sử.**
