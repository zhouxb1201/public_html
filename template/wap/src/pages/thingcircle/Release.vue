<template>
  <div class="thingcircle-release">
    <Navbar :isMenu="false" />
    <div class="card-group-box">
      <van-field placeholder="加个标题会有更多赞哦！最多20字" maxlength="20" v-model="thing_title" />
      <van-field
        v-model="content"
        type="textarea"
        maxlength="1000"
        placeholder="说说此刻心情。最多1000字"
        rows="1"
        :autosize="autosize"
        class="textarea"
      />
      <div class="upload-wrap">
        <ImagePanelPreview :list="arrImg">
          <div slot="upload">
            <UploadImages
              :total="arrImg.length"
              :maxNum="9"
              multiple
              type="evaluate"
              @finish="onUploadFinish"
              class="upload-img"
            />
          </div>
        </ImagePanelPreview>
      </div>
      <ChoiceGoods @arr-goodsId="getGoodsId" />
      <PartTopics @click-topics="getTopics" />
      <AddLocation @add-location="getLocation" />
    </div>
    <div class="btn">
      <van-button type="danger" size="normal" @click="onSubmit">发布干货</van-button>
    </div>
  </div>
</template>

<script>
import UploadImages from "@/components/UploadImages";
import ImagePanelPreview from "@/components/ImagePanelPreview";
import ChoiceGoods from "./component/publish/ChoiceGoods";
import AddLocation from "./component/publish/AddLocation";
import PartTopics from "./component/publish/PartTopics";
import { ADD_RELEASEDRY, GET_SHAREINFO } from "@/api/thingcircle";
import { filterUriParams } from "@/utils/util";
import sfc from "@/utils/create";
export default sfc({
  name: "thingcircle-release",
  data() {
    return {
      thing_title: "",
      content: "",
      autosize: {
        maxHeight: 100,
        minHeight: 100
      },
      arrImg: [],
      goodsId: "",
      address: {},
      topics_info: {},
      flag: true
    };
  },
  mounted() {
    this.getShareInfo();
  },
  methods: {
    onUploadFinish({ src }, index) {
      this.arrImg.push(src);
    },
    getGoodsId(id) {
      this.goodsId = id;
    },
    getLocation(address) {
      this.address = {
        name: address.name,
        lat: address.lat,
        lng: address.lng
      };
    },
    getTopics(info) {
      this.topics_info = {
        topic_id: info.topic_id,
        topic_title: info.title
      };
    },
    onSubmit() {
      const $this = this;
      let params = {};
      params.thing_type = 1;
      params.topic_id = $this.topics_info.topic_id
        ? $this.topics_info.topic_id
        : "";
      params.content = $this.content.replace(/\s*/g, "");
      params.thing_title = $this.thing_title;
      params.img_id = $this.arrImg.join();
      params.goods_array = $this.goodsId;
      params.location = $this.address.name ? $this.address.name : "";
      params.lat = $this.address.lat ? $this.address.lat : "";
      params.lng = $this.address.lng ? $this.address.lng : "";
      if (!params.content) {
        $this.$Toast("请填写话题内容");
        return false;
      }
      if (!params.img_id) {
        $this.$Toast("请添加话题图片");
        return false;
      }
      if ($this.flag == false) {
        return false;
      }
      $this.flag = false;
      ADD_RELEASEDRY(params).then(res => {
        if (res.code == 1) {
          $this.$Toast.success(res.message);
          setTimeout(() => {
            $this.$router.replace("/thingcircle/index");
          }, 100);
        }
      });
    },
    getShareInfo() {
      const $this = this;
      GET_SHAREINFO().then(({ data }) => {
        $this.onShare({
          title: data.other_title ? data.other_title : "好物圈",
          desc: data.other_describe
            ? data.other_describe
            : `我刚刚在${$this.$store.getters.config.mall_name}发现了一个很不错的好物圈，赶快来看看吧。`,
          imgUrl: data.other_pic,
          link:
            $this.$store.state.domain +
            "/wap" +
            $this.$route.path +
            filterUriParams($this.$route.query, "extend_code")
        });
      });
    }
  },
  components: {
    ImagePanelPreview,
    UploadImages,
    ChoiceGoods,
    AddLocation,
    PartTopics
  }
});
</script>

<style scoped>
.card-group-box {
  background-color: #ffffff;
}
.card-group-box >>> .van-cell:not(:last-child)::after {
  left: 0;
}
.nowrap >>> .van-cell__value {
  text-overflow: ellipsis;
  white-space: nowrap;
}
.btn {
  margin: 40px 20px;
}
.btn >>> .van-button {
  border-radius: 6px;
  width: 100%;
}
/**upload-start**/
.upload-wrap {
  overflow: hidden;
  padding: 15px 15px 15px;
  background-color: #ffffff;
  position: relative;
}
.upload-wrap::after {
  content: "";
  position: absolute;
  pointer-events: none;
  -webkit-box-sizing: border-box;
  box-sizing: border-box;
  bottom: 0;
  left: 0;
  width: 100%;
  transform: scaleY(0.5);
  height: 1px;
  background-color: #ebedf0;
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
  border-radius: 10px;
}
.upload-wrap >>> .img-group img {
  display: block;
  border-radius: 10px;
}
.upload-wrap >>> .btn-delete {
  right: -5px;
  top: -4px;
  font-size: 20px;
  color: #f02941;
}
/*upload-end*/
</style>