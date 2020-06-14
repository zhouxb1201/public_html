<template>
  <Layout ref="load" class="course-detail bg-f8">
    <Navbar />

    <PlayVedio 
      v-if="imgSource.type == 1"
      :data="imgSource"
      :goodsId="$route.params.id"
    />

    <PlayAudio :data="imgSource" :goodsId="$route.params.id" v-if="imgSource.type == 2" />

    <div class="picture" v-if="imgSource.type == 3">
      <div v-if="!imgSource.is_buy && imgSource.is_see == -1">
        <img :src="$BASEIMGPATH+'empty-data.png'" :style="maxHeight" />
        <div class="source__title">{{imgSource.name}}</div>

        <div class="audio-overlay">
            <div class="audio-overlay-text">试学结束，购买后查看完整版</div>
                <van-button
                  type="danger"
                  size="small"
                  :to="'/goods/detail/'+ $route.params.id"
                >立即购买</van-button>
        </div>
      </div>
      <div v-else>
        <img :src="imgSource.content" :style="maxHeight" />
        <div class="source__title">{{imgSource.name}}</div>
      </div>

      <!--<img :src="$BASEIMGPATH+'empty-data.png'" :style="maxHeight" />
      <div class="source__title">{{imgSource.name}}</div>-->
    </div>

    <Card :id="'/goods/detail/'+ $route.params.id" :image="imgSource.goods_picture" :name="imgSource.goods_name" :total_count="imgSource.total_count" buttomText="立即购买" :showbtn="!imgSource.is_buy" />
    <Evaluate :data="$route.params.id" />

    <div 
       class="d-box" 
       @click="changeshow">
        <van-icon name="orders-o" />
        <div class="d-box-text">目录</div> 
    </div>
    <Directory  @closeTip="changeshow" :showother="show" :knowledgePaymentId="imgSource.id" ></Directory>


  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { GET_GOODSDETAIL } from "@/api/course";
import PlayVedio from "./component/detail/PlayVedio";
import PlayAudio from "./component/detail/PlayAudio";
import Card from "./component/Card";
import Evaluate from "./component/Evaluate";
import Directory from "./component/Directory";
export default sfc({
  name: "course-detail",
  data() {
    return {
      imgSource: {},
      params: {
        goods_id:this.$route.params.id,
        knowledge_payment_id:this.$route.params.cid || ''
      },
      show:false,
      update: true
    };
  },
  computed: {
    maxHeight() {
      return {
        maxHeight: document.body.offsetWidth + "px",
        width: "100%",
        height: "auto",
      };
    },
  },
  watch: {},
  mounted() {
    this.loadDataDetail();
  },
  methods: {
    loadDataDetail(){
      GET_GOODSDETAIL(this.params)
        .then(({ data }) => {
          this.imgSource = data;
          this.$refs.load.success();
        })
        .catch(() => {
          this.$refs.load.fail();
        });
    },
    changeshow(obj) {
      //子组件关闭事件触发的
      if (obj.flg) {
        this.show = false;
      } else {
        //父级点击按钮触发
        this.show = !this.show;
      };
      if (obj.id) {
        this.imgSource = {}
        GET_GOODSDETAIL({knowledge_payment_id:obj.id})
          .then(({ data }) => {
            this.imgSource = data;
          })
          .catch(() => {
          });
      }
    },

  },
  components: {
    PlayVedio,
    PlayAudio,
    Card,
    Evaluate,
    Directory
  }
});
</script>

<style scoped>
.source__title {
  padding: 5px;
  line-height: 16px;
  max-height: 42px;
  font-weight: 700;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  word-break: break-all;
  background-color: #fff;
}

.d-box {
  position: fixed;
  background-color: #fff;
  width: 50px;
  height: 50px;
  border-radius: 50%;
  color: #606266;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  font-size: 18px;
  box-shadow: 0 0 6px rgba(0, 0, 0, 0.12);
  cursor: pointer;
  z-index: 999;
  opacity: 0.7;
  right: 15px;
  bottom: 15px;
}
.d-box .d-box-text{
  font-size: 12px;
  margin-top: 4px;
}
.picture{
  position: relative;
}
.picture .audio-overlay{
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 90%;
    background-color: rgba(0,0,0,.7);
    color: #fff;
    text-align: center;
    padding-top: 100px;
}
.picture .audio-overlay .audio-overlay-text{
    margin-bottom: 20px;
}
</style>
