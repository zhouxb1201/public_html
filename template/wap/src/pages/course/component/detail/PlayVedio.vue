<template>
  <div class="vedio-wrap">
    <VideoPlayer :options="options" @currentTime="currentTime1" ref="videoPlayer" />
    <div class="title">
        <van-tag class="tag" round size="medium" color="#FAE9E6" text-color="#ff454e" v-if="data.is_see > 0 && !data.is_buy">试学{{data.is_see}}分钟</van-tag>
        {{data.name}}
    </div>

    <div class="audio-overlay" v-show="audioOverlayShow">
        <div class="audio-overlay-text">试学结束，购买后查看完整版</div>
            <van-button
              type="danger"
              size="small"
              :to="'/goods/detail/'+ goodsId"
            >立即购买</van-button>
    </div>

  </div>
</template>

<script>
import VideoPlayer from "@/components/VideoPlayer";
export default {
  data() {
    return {
      audioOverlayShow : false,
    };
  },
  computed:{
    options(){
      let option = {
        height: "300px",
        autoplay: false,
        sources: [
          {
            type: "video/mp4",
            src: this.data.content
          }
        ],
        playsinline: true
      }
      return option
    }
  },
  mounted() {
    this.isSee();
  },
  watch:{
    'data.is_see':{
        handler(newval,oldval){
            if(newval>0){
                this.audioOverlayShow = false;
            }else if(!this.data.is_buy && newval == -1){
                this.audioOverlayShow = true;
            }else{
                this.audioOverlayShow = false;
            }
        }
    },
  },
  methods: {
    currentTime1(currentTimeId) {
      let seconds = this.data.is_see * 60;
      if(!this.data.is_buy && this.data.is_see>0){
          if( currentTimeId > seconds ){
              this.options.sources[0].src = '';
              this.audioOverlayShow = true;
          }
      }

    },
    isSee() {
        console.log('isSee');
        if(!this.data.is_buy && this.data.is_see == -1){
            this.audioOverlayShow = true;
            this.options.sources[0].src = '';
        }
    },
    
  },
  props: {
    data: [Object, Array],
    goodsId:[String, Number],
  },
  components: {
    VideoPlayer
  }
};
</script>

<style scoped>
.vedio-wrap .title {
    line-height: 16px;
    max-height: 40px;
    /*height: 35px;*/
    font-weight: 700;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    word-break: break-all;
    background-color: #fff;
    padding:5px;
}
.vedio-wrap{
  position: relative;
}
.vedio-wrap .audio-overlay{
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
.vedio-wrap .audio-overlay .audio-overlay-text{
    margin-bottom: 20px;
}
.vedio-wrap >>> .vjs-error .vjs-error-display .vjs-modal-dialog-content{
  display: none;
}
.vedio-wrap >>> .vjs-error-display{
  display: none;
}
</style>