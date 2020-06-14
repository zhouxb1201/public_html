<template>
  <div class="thingcirle-report">
    <Navbar :isMenu="false" />
    <div v-if="!isSuccess">
      <div class="cell-wrap">
        <van-cell title="违规类型" />
        <van-radio-group v-model="type">
          <van-cell-group v-for="(item,index) in reportAction" :key="index">
            <van-cell :title="item.name" clickable @click="onSelect(item.violation_id)">
              <van-radio :name="item.violation_id" />
            </van-cell>
          </van-cell-group>
          <van-field
            v-model="message"
            type="textarea"
            placeholder="请描述你的举报内容"
            rows="1"
            :autosize="autosize"
          />
        </van-radio-group>
      </div>
      <div class="upload-wrap">
        <ImagePanelPreview :list="arrImg">
          <div slot="upload">
            <UploadImages
              :total="arrImg.length"
              :maxNum="3"
              multiple
              type="evaluate"
              @finish="onUploadFinish"
              class="upload-img"
            />
          </div>
        </ImagePanelPreview>
      </div>
      <div class="btn">
        <van-button type="danger" size="normal" @click="onSubmit">举报</van-button>
      </div>
    </div>
    <div class="success" v-else>
      <van-icon name="checked" size="100px" />
      <h3>举报成功</h3>
      <p>受理结果将会在消息通知中告知</p>
    </div>
  </div>
</template>

<script>
import UploadImages from "@/components/UploadImages";
import ImagePanelPreview from "@/components/ImagePanelPreview";
import { GET_VIOLATIONLIST, ADD_VIOLATION } from "@/api/thingcircle";
import sfc from "@/utils/create";
export default sfc({
  name: "thingcirle-report",
  data() {
    return {
      message: "",
      autosize: {
        maxHeight: 100,
        minHeight: 100
      },
      type: "",
      reportAction: [],
      violation_id: null,
      arrImg: [],
      isSuccess: false
    };
  },
  computed: {
    commentid() {
      return this.$route.params.commentid;
    }
  },
  mounted() {
    this.loadData();
  },
  methods: {
    loadData() {
      const $this = this;
      GET_VIOLATIONLIST().then(({ data }) => {
        $this.reportAction = data.data;
      });
    },
    onUploadFinish({ src }, index) {
      this.arrImg.push(src);
    },
    onSelect(id) {
      const $this = this;
      $this.type = id;
      $this.violation_id = id;
    },
    onSubmit() {
      const $this = this;
      let params = {
        comment_id: $this.commentid,
        violation_id: $this.violation_id,
        report_reason: $this.message,
        report_photo: $this.arrImg.join()
      };
      if (!params.violation_id) {
        $this.$Toast("请选择违规类型");
        return false;
      }
      if (!params.report_reason) {
        $this.$Toast("请填写举报内容");
        return false;
      }
      if (!params.report_photo) {
        $this.$Toast("请上传举报图片");
        return false;
      }

      ADD_VIOLATION(params)
        .then(res => {
          if (res.code > 0) {
            $this.isSuccess = true;
          }
        })
        .catch(err => {});
    }
  },
  components: {
    ImagePanelPreview,
    UploadImages
  }
});
</script>

<style scoped>
.icon {
  font-size: 20px;
  width: 30px;
  height: 24px;
  text-align: center;
  line-height: 24px;
  margin-right: 4px;
}
.van-cell.disabled {
  color: #999;
  background-color: #e8e8e8;
}
.cell-wrap >>> .van-cell:not(:last-child)::after {
  border-bottom: none;
}
/**upload-start**/
.upload-wrap {
  overflow: hidden;
  padding: 0px 15px 15px;
  background-color: #ffffff;
}
.upload-wrap >>> .upload-icon {
  font-size: 38px;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -70%);
}
.upload-wrap >>> .uploader {
  position: relative;
  display: flex;
  flex-flow: column;
  text-align: center;
  font-size: 12px;
  color: #666;
  border: 1px dashed #ddd;
  line-height: 1.2;
  padding-bottom: 100%;
  width: auto;
}
.upload-wrap >>> .uploader div {
  position: absolute;
  bottom: 6%;
  left: 50%;
  transform: translateX(-50%);
  font-size: 14px;
}
.upload-img {
  width: calc(25% - 8px);
  float: left;
  margin: 4px;
}
.upload-wrap >>> .img-group .item {
  width: calc(25% - 8px);
}
.upload-wrap >>> .img-group .box {
  height: 100%;
  padding: 0 0 100% 0;
  overflow: hidden;
  background: #f9f9f9;
  border-radius: 10px;
}
.upload-wrap >>> .img-group img {
  display: block;
  position: absolute;
  border-radius: 10px;
  margin-top: 0;
}
.upload-wrap >>> .btn-delete {
  right: -5px;
  top: -4px;
  font-size: 20px;
  color: #f02941;
}
/*upload-end*/
.btn {
  margin: 40px 20px;
}
.btn >>> .van-button {
  border-radius: 6px;
  width: 100%;
}
.success {
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  padding: 50px;
  text-align: center;
  flex-direction: column;
}
.success >>> .van-icon {
  color: #07c160;
  font-size: 100px;
}
.success h3 {
  font-weight: normal;
  line-height: 40px;
}
.success p {
  font-size: 12px;
  color: #999;
}
</style>