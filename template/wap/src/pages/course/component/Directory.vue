<template>
    <div class="directory">
      <van-popup v-model="myshow" closeable :duration='0.3' position="bottom" 
      @click-overlay="close" @close="close">
      
      <div class="van-hairline--top-bottom van-actionsheet__header">
        <div>课程目录</div>
        <van-icon name="close" @click="close" />
      </div>

      <van-cell-group>
        <van-cell v-for="(item,index) in source" :key="index" @click="close1(item.knowledge_payment_id)">
          <van-col span="18" :class="{ 'active': item.knowledge_payment_id ==knowledgePaymentId }">
            {{item.knowledge_payment_name}}

          </van-col>
          <van-col span="6" v-if="!is_buy">
                <div class="same" v-if="item.knowledge_payment_id ==knowledgePaymentId">
                  <span class="line1"></span>
                  <span class="line2"></span>
                  <span class="line3"></span>
                  <span class="line4"></span>
                </div>
                <van-tag
                  class="tag"
                  round
                  size="medium"
                  color="#FAE9E6"
                  text-color="#ff454e"
                  v-if="item.knowledge_payment_is_see == -1"
                > 付费浏览</van-tag>
                <van-tag
                  class="tag"
                  round
                  size="medium"
                  color="#FAE9E6"
                  text-color="#ff454e"
                  v-if="item.knowledge_payment_is_see > 0"
                >试学</van-tag>
          </van-col>
          <van-col span="6" v-else>
              <div class="same" v-if="item.knowledge_payment_id ==knowledgePaymentId">
                <span class="line1"></span>
                <span class="line2"></span>
                <span class="line3"></span>
                <span class="line4"></span>
              </div>
          </van-col>
        </van-cell>
      </van-cell-group>

      </van-popup>
    </div>
</template>
<script>
import { Popup } from 'vant';
import { GET_GOODSDETAIL_LIST} from "@/api/course";
export default {
    name:'getOrder',
    props:{
      knowledgePaymentId: [String, Number],
      showother: {
        type: Boolean,
        // default: true
      },
    },
    data(){
        return{
           myshow:this.showother,//映射
           source:{},
           is_buy:'',
           params: {
              goods_id: this.$route.params.id,
          },
        }
    },
  computed: {

  },
  mounted() {
    const $this = this;
    $this.loadData();
  },
  methods: {
    close(){
      this.$emit("closeTip",{"flg":"1"});
    },
    close1(id){
      this.$emit("closeTip",{"flg":"1","id":id});
    },
    loadData() {
      const $this = this;
      GET_GOODSDETAIL_LIST(this.params)
        .then(({ data }) => {
          $this.source = data.konwledge_payment_list;
          $this.is_buy = data.is_buy;
        })
    },

  },
  watch:{
      showother:{
          handler(newval,oldval){
          this.myshow= newval;
          }
      }
  },
  components: {
    Popup
  }
}
</script>

<style scoped>
.active{
  color: #FF454E;
}
.d-title{
  padding: 10px;
  text-align: center;
  font-size: 20px;
  line-height: 20px;
  border-bottom: 1px solid #ccc;
}
.directory >>> .van-cell-group{
  height: 300px;
  overflow-y: scroll;
}
.directory >>> .van-col--18{
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  word-break: break-all;
}
.directory >>> .van-col--6{
  text-align: right;
}
.directory .same{
  display: inline-block;
}
.directory .same span{
    display: inline-block;
    width: 2px;
    height: 10px;
    margin-bottom: 0;
    background-color: red;
    margin-left: -2px;
}
.directory .same span.line1{
    -webkit-animation: line .6s infinite ease-in-out alternate;
    -moz-animation: line .6s infinite ease-in-out alternate;
    animation: line .6s infinite ease-in-out alternate;
}
.directory .same span.line2{
    -webkit-animation: line .6s .2s infinite ease-in-out alternate;
    -moz-animation: line .6s .2s infinite ease-in-out alternate;
    animation: line .6s .2s infinite ease-in-out alternate;
}
.directory .same span.line3{
    -webkit-animation: line .6s .3s infinite ease-in-out alternate;
    -moz-animation: line .6s .3s infinite ease-in-out alternate;
    animation: line .6s .3s infinite ease-in-out alternate;
}
.directory .same span.line4{
    -webkit-animation: line .6s .15s infinite ease-in-out alternate;
    -moz-animation: line .6s .15s infinite ease-in-out alternate;
    animation: line .6s .15s infinite ease-in-out alternate;
}
@keyframes line {
    0% {
        height: 1px
    }

    to {
        height: 15px
    }
}

@-webkit-keyframes line {
    0% {
        height: 1px
    }

    to {
        height: 15px
    }
}
</style>